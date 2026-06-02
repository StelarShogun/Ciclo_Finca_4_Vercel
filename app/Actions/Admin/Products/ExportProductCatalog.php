<?php

namespace App\Actions\Admin\Products;

use App\Models\Product;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\AdminPdfExportService;
use App\Services\Admin\Products\AdminInventoryProductQuery;
use App\Services\Admin\RegistryExcelExport;
use App\Services\Admin\ReportExcelFilename;
use App\Support\ProductCatalog\ProductCatalogExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class ExportProductCatalog
{
    public function __construct(
        private AdminInventoryProductQuery $inventoryQuery,
        private ProductCatalogExporter $catalogExporter,
    ) {}

    public function handle(Request $request, mixed $format = null): Response|JsonResponse|BinaryFileResponse
    {
        $format = strtolower($format ?? $request->get('format', 'pdf'));

        $exportAll = $request->query('scope') === 'all';
        $baseQuery = $exportAll
            ? $this->inventoryQuery->filteredQuery(new Request)
            : $this->inventoryQuery->filteredQuery($request);
        $filterLines = $exportAll
            ? ['Inventario: todo (sin filtros)']
            : $this->inventoryQuery->exportFilterLines($request);

        $withRelations = [
            'category.parent',
            'supplier:supplier_id,name',
            'brands:id,name',
            'classificationValues.dimension',
            'variants:product_id,name,sku',
        ];

        if (in_array($format, ['bundle', 'zip'], true)) {
            $manifest = $this->catalogExporter->buildManifest($baseQuery, $exportAll);
            $products = (clone $baseQuery)->with($withRelations)->limit(10_000)->get();
            $zipPath = storage_path('app/temp/catalog-export-'.Str::uuid().'.zip');
            if (! is_dir(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }
            $this->catalogExporter->writeBundleZip($zipPath, $products, $manifest);
            $filename = 'catalogo_productos_'.date('Ymd_His').'.zip';

            return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
        }

        if ($format === 'json') {
            $manifest = $this->catalogExporter->buildManifest($baseQuery, $exportAll);
            $filename = 'catalogo_productos_'.date('Ymd_His').'.json';

            return response()->json($manifest, 200, [
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if ($format === 'xml') {
            return $this->xmlResponse($baseQuery, $withRelations, $exportAll);
        }

        if ($format === 'pdf') {
            return $this->pdfResponse($baseQuery, $withRelations, $filterLines);
        }

        if ($format === 'excel') {
            return $this->excelResponse($baseQuery, $withRelations, $filterLines);
        }

        return response()->json([
            'success' => false,
            'message' => 'Formato no soportado. Use bundle (ZIP completo), json, xml, pdf o excel.',
        ], 400);
    }

    /**
     * @param  list<string>  $withRelations
     */
    private function xmlResponse($baseQuery, array $withRelations, bool $exportAll): Response
    {
        $maxRows = $exportAll ? 10_000 : AdminPdfExportLimits::INVENTORY_MAX_ROWS;
        $data = (clone $baseQuery)->with($withRelations)->limit($maxRows)->get();
        $xml = new \SimpleXMLElement('<catalog/>');
        $xml->addAttribute('version', (string) ProductCatalogExporter::MANIFEST_VERSION);
        $xml->addChild('exported_at', now()->toIso8601String());
        foreach ($data as $p) {
            if (! $p instanceof Product) {
                continue;
            }
            $arr = $this->catalogExporter->productToArray($p);
            $n = $xml->addChild('product');
            foreach ($arr as $key => $value) {
                if (is_array($value)) {
                    $child = $n->addChild($key);
                    foreach ($value as $subKey => $subVal) {
                        if (is_array($subVal)) {
                            $sub = $child->addChild(is_int($subKey) ? 'item' : (string) $subKey);
                            foreach ($subVal as $item) {
                                $sub->addChild('value', htmlspecialchars((string) $item));
                            }
                        } else {
                            $child->addChild((string) $subKey, htmlspecialchars((string) $subVal));
                        }
                    }
                } else {
                    $n->addChild((string) $key, htmlspecialchars((string) $value));
                }
            }
        }
        $filename = 'products_'.date('Ymd_His').'.xml';

        return response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * @param  list<string>  $withRelations
     * @param  list<string>  $filterLines
     */
    private function pdfResponse($baseQuery, array $withRelations, array $filterLines): Response
    {
        $maxRows = AdminPdfExportLimits::INVENTORY_MAX_ROWS;
        $totalMatching = (clone $baseQuery)->count();

        $pdfFilterLines = $filterLines;
        if ($totalMatching > $maxRows) {
            $pdfFilterLines[] = 'Nota: el PDF incluye como máximo '.$maxRows.' productos ('.$totalMatching.' coinciden con los filtros).';
        }

        $pdfRows = (clone $baseQuery)
            ->with($withRelations)
            ->limit($maxRows)
            ->get();

        $products = $pdfRows->map(function ($p) {
            if (! $p instanceof Product) {
                return null;
            }

            return (object) [
                'id' => $p->product_id,
                'name' => $p->name,
                'description' => $p->description ?? 'No description',
                'category' => optional($p->category)->name ?? 'Uncategorized',
                'supplier' => optional($p->supplier)->name ?? 'No supplier',
                'purchase_price' => number_format((float) $p->purchase_price, 2),
                'sale_price' => number_format((float) $p->sale_price, 2),
                'stock_current' => $p->stock_current,
                'stock_minimum' => $p->stock_minimum,
                'status' => ucfirst(str_replace('_', ' ', $p->status)),
                'created_at' => $p->created_at ? $p->created_at->format('d/m/Y') : 'N/A',
            ];
        })->filter()->values();

        $logoPath = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        return app(AdminPdfExportService::class)->download('admin.products.products-pdf', [
            'products' => $products,
            'total' => $products->count(),
            'totalMatching' => $totalMatching,
            'fecha_exportacion' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'pdfTitle' => 'Reporte de inventario',
            'pdfSubtitle' => 'Productos filtrados — Ciclo Finca 4',
            'logoPath' => is_file($logoPath) ? $logoPath : null,
            'filterLines' => $pdfFilterLines,
            'generatedFor' => 'Administración',
        ], 'inventario');
    }

    /**
     * @param  list<string>  $withRelations
     * @param  list<string>  $filterLines
     */
    private function excelResponse($baseQuery, array $withRelations, array $filterLines): Response
    {
        $maxRows = AdminPdfExportLimits::INVENTORY_MAX_ROWS;
        $totalMatching = (clone $baseQuery)->count();

        $excelFilterLines = $filterLines;
        if ($totalMatching > $maxRows) {
            $excelFilterLines[] = 'Nota: el Excel incluye como máximo '.$maxRows.' productos ('.$totalMatching.' coinciden con los filtros).';
        }

        $rows = (clone $baseQuery)
            ->with($withRelations)
            ->limit($maxRows)
            ->get();

        $headers = [
            'ID', 'SKU', 'Nombre', 'Descripción', 'Categoría padre', 'Subcategoría', 'Proveedor', 'Marca(s)',
            'Precio compra', 'Precio venta', 'Stock actual', 'Stock mínimo', 'Estado', 'Destacado',
            'Clasificaciones', 'Variantes (SKU)', 'Creado',
        ];
        $dataRows = $rows->map(function ($p) {
            if (! $p instanceof Product) {
                return null;
            }
            $arr = $this->catalogExporter->productToArray($p);
            $classStr = collect($arr['classifications'] ?? [])->map(fn ($v, $k) => $k.': '.$v)->implode('; ');

            return [
                (string) $p->product_id,
                $arr['display_sku'] ?? '',
                $p->name,
                $p->description ?? '',
                $arr['parent_category'] ?? '',
                $arr['category'] ?? '',
                optional($p->supplier)->name ?? '',
                implode(', ', $arr['brands'] ?? []),
                number_format((float) $p->purchase_price, 2, '.', ''),
                number_format((float) $p->sale_price, 2, '.', ''),
                (string) $p->stock_current,
                (string) $p->stock_minimum,
                $p->status,
                ($p->is_featured ?? false) ? '1' : '0',
                $classStr,
                implode(', ', $arr['variant_export_keys'] ?? []),
                $p->created_at ? $p->created_at->format('Y-m-d H:i:s') : '',
            ];
        })->filter()->values()->all();

        return app(RegistryExcelExport::class)->download(
            'Inventario de productos',
            'Catálogo de inventario — Ciclo Finca 4',
            $headers,
            $dataRows,
            $excelFilterLines,
            ReportExcelFilename::make('inventario'),
        );
    }
}
