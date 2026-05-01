<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Admin\AdminInventoryExportQuery;
use App\Services\Admin\AdminPdfExportService;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\RegistryExcelExport;
use App\Services\Admin\ReportExcelFilename;
use App\Services\Admin\ReportPdfFilename;
use App\Services\AuditLogger;
use App\Services\InventoryMovementService;
use App\Services\ProductClassificationAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $perPage = $request->get('per_page', 10);
            $products = Product::with(['category', 'supplier'])
                ->orderBy('product_id', 'desc')
                ->paginate($perPage);

            return response()->json($products);
        }

        return $this->inventory($request);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $auditContext = null;

            $product = DB::transaction(function () use ($request) {
                $data = $request->validated();
                $brandId = $data['brand_id'];
                unset($data['brand_id']);
                $classificationIds = $data['classification_value_ids'] ?? [];
                unset($data['classification_value_ids']);

                // File inputs are processed after the product is created
                unset($data['image'], $data['images']);

                $product = Product::create($data);
                $product->brands()->attach($brandId);
                app(ProductClassificationAssignmentService::class)->syncForProduct($product, $classificationIds);

                return $product;
            });

            $auditContext = [
                'product_id' => (int) $product->product_id,
                'name' => $product->name,
                'category_id' => (int) $product->category_id,
                'supplier_id' => (int) $product->supplier_id,
                'status' => (string) $product->status,
            ];

            // Store uploaded files locally before registering them in MediaLibrary
            $folderPath = public_path('images/'.$product->name);
            if (! is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $slug = Str::slug($product->name, '_');

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $ext = $file->extension() ?: $file->getClientOriginalExtension();
                $filename = $slug.'_main.'.$ext;
                $file->move($folderPath, $filename);
                $product->addMedia($folderPath.'/'.$filename)
                    ->preservingOriginal()
                    ->toMediaCollection('main_image');
            }

            if ($request->hasFile('images')) {
                $i = 2;
                foreach ($request->file('images') as $file) {
                    if (! $file->isValid() || ! str_starts_with($file->getMimeType() ?? '', 'image/')) {
                        continue;
                    }
                    $ext = $file->extension() ?: $file->getClientOriginalExtension();
                    $filename = $slug.'_'.$i.'.'.$ext;
                    $file->move($folderPath, $filename);
                    $product->addMedia($folderPath.'/'.$filename)
                        ->preservingOriginal()
                        ->toMediaCollection('gallery');
                    $i++;
                }
            }

            $this->logAudit('product_create', 'Producto creado.', $auditContext ?? []);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto creado con éxito',
                    'data' => $product->load(['category.parent', 'supplier', 'classificationValues.dimension']),
                ]);
            }

            return redirect()->route('inventory')->with('status', 'Producto creado con éxito');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Revisa los errores en el formulario.',
                ], 422);
            }

            return redirect()->back()->withErrors($errors)->withInput();
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo crear el producto. Inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->back()->with('error', 'No se pudo crear el producto. Inténtalo de nuevo.')->withInput();
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with(['category.parent', 'supplier', 'brands', 'classificationValues', 'variants'])->findOrFail($id);

            if (request()->wantsJson() || request()->ajax()) {
                $productData = $product->toArray();
                $firstBrand = $product->brands->first();
                $productData['brand_id'] = $firstBrand instanceof Brand ? $firstBrand->id : null;
                $productData['classification_value_ids'] = $product->classificationValues->pluck('id')->values()->all();
                $productData['media_main'] = $product->getFirstMediaUrl('main_image');
                $productData['media_gallery'] = $product->getMedia('gallery')->map(fn ($m) => $m->getUrl())->values()->toArray();
                $productData['variants'] = $product->variants
                    ->map(fn (Product $v) => [
                        'product_id' => (int) $v->product_id,
                        'name' => (string) $v->name,
                        'status' => (string) $v->status,
                        'stock_current' => (int) $v->stock_current,
                        'sale_price' => (string) $v->sale_price,
                    ])
                    ->values()
                    ->all();

                return response()->json([
                    'success' => true,
                    'data' => $productData,
                ]);
            }

            return view('products.show', compact('product'));
        } catch (ModelNotFoundException $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado',
                ], 404);
            }

            return redirect()->route('inventory')->with('error', 'Producto no encontrado');
        } catch (\Throwable $e) {
            Log::error('Product show failed.', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                $payload = [
                    'success' => false,
                    'message' => 'No se pudo cargar el producto. Inténtalo de nuevo.',
                ];

                if (config('app.debug')) {
                    $payload['error'] = $e->getMessage();
                }

                return response()->json($payload, 500);
            }

            return redirect()->route('inventory')->with('error', 'No se pudo cargar el producto. Inténtalo de nuevo.');
        }
    }

    public function toggleFeatured(int $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->is_featured = ! $product->is_featured;
            $product->save();

            $this->logAudit(
                'product_toggle_featured',
                $product->is_featured ? 'Producto marcado como destacado.' : 'Producto removido de destacados.',
                [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'is_featured' => (bool) $product->is_featured,
                ]
            );

            return response()->json([
                'success' => true,
                'is_featured' => (bool) $product->is_featured,
                'message' => $product->is_featured
                    ? 'Producto marcado como destacado en la tienda (inicio y catálogo).'
                    : 'Producto quitado de destacados en la tienda.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el destacado. Inténtalo de nuevo.',
            ], 500);
        }
    }

    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $auditContext = null;

            $product = DB::transaction(function () use ($request, $id) {
                $p = Product::findOrFail($id);
                $before = [
                    'name' => $p->name,
                    'description' => $p->description,
                    'category_id' => (int) $p->category_id,
                    'supplier_id' => (int) $p->supplier_id,
                    'purchase_price' => (float) $p->purchase_price,
                    'sale_price' => (float) $p->sale_price,
                    'stock_current' => (int) $p->stock_current,
                    'stock_minimum' => (int) $p->stock_minimum,
                    'status' => (string) $p->status,
                    'is_featured' => (bool) $p->is_featured,
                ];
                $data = $request->validated();
                $brandId = $data['brand_id'];
                unset($data['brand_id']);
                $syncClassifications = $request->has('classification_value_ids');
                $classificationIds = $syncClassifications ? ($data['classification_value_ids'] ?? []) : null;
                unset($data['classification_value_ids']);

                // File inputs are processed after the product is updated
                unset($data['image'], $data['images']);

                $p->update($data);
                $p->brands()->sync([$brandId]);
                $p->refresh();
                if ($syncClassifications) {
                    app(ProductClassificationAssignmentService::class)->syncForProduct($p, $classificationIds ?? []);
                }

                $after = [
                    'name' => $p->name,
                    'description' => $p->description,
                    'category_id' => (int) $p->category_id,
                    'supplier_id' => (int) $p->supplier_id,
                    'purchase_price' => (float) $p->purchase_price,
                    'sale_price' => (float) $p->sale_price,
                    'stock_current' => (int) $p->stock_current,
                    'stock_minimum' => (int) $p->stock_minimum,
                    'status' => (string) $p->status,
                    'is_featured' => (bool) $p->is_featured,
                ];

                return [$p, $before, $after];
            });

            [$product, $before, $after] = $product;
            $changed = [];
            foreach ($after as $field => $value) {
                if (($before[$field] ?? null) !== $value) {
                    $changed[$field] = [
                        'from' => $before[$field] ?? null,
                        'to' => $value,
                    ];
                }
            }

            $auditContext = [
                'product_id' => (int) $product->product_id,
                'changes' => $changed,
            ];

            // Store uploaded files locally before registering them in MediaLibrary
            $folderPath = public_path('images/'.$product->name);
            if (! is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $slug = Str::slug($product->name, '_');

            // Replace the main image when a new file is uploaded
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $ext = $file->extension() ?: $file->getClientOriginalExtension();
                // Remove the previous main image file from disk
                foreach (glob($folderPath.'/'.$slug.'_main.*') ?: [] as $old) {
                    @unlink($old);
                }
                $filename = $slug.'_main.'.$ext;
                $file->move($folderPath, $filename);
                $product->addMedia($folderPath.'/'.$filename)
                    ->preservingOriginal()
                    ->toMediaCollection('main_image');
            }

            // Replace the entire gallery when new files are provided
            if ($request->hasFile('images')) {
                // Remove existing gallery files from disk
                foreach (glob($folderPath.'/'.$slug.'_[0-9]*.{jpg,jpeg,png,webp,gif,avif}', GLOB_BRACE) ?: [] as $old) {
                    @unlink($old);
                }
                $product->clearMediaCollection('gallery');
                $i = 2;
                foreach ($request->file('images') as $file) {
                    if (! $file->isValid() || ! str_starts_with($file->getMimeType() ?? '', 'image/')) {
                        continue;
                    }
                    $ext = $file->extension() ?: $file->getClientOriginalExtension();
                    $filename = $slug.'_'.$i.'.'.$ext;
                    $file->move($folderPath, $filename);
                    $product->addMedia($folderPath.'/'.$filename)
                        ->preservingOriginal()
                        ->toMediaCollection('gallery');
                    $i++;
                }
            }

            $this->logAudit(
                'product_update',
                'Producto actualizado.',
                $auditContext ?? ['product_id' => (int) $id]
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto actualizado con éxito',
                    'data' => $product->load(['category.parent', 'supplier', 'classificationValues.dimension']),
                ]);
            }

            return redirect()->route('inventory')->with('status', 'Producto actualizado con éxito');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Revisa los errores en el formulario.',
                ], 422);
            }

            return redirect()->back()->withErrors($errors)->withInput();
        } catch (\Throwable $e) {
            Log::error('Error updating product: '.$e->getMessage(), [
                'product_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo actualizar el producto. Inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->back()->with('error', 'No se pudo actualizar el producto. Inténtalo de nuevo.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $productName = null;
            DB::transaction(function () use ($id, &$productName) {
                $p = Product::findOrFail($id);
                $productName = $p->name;
                // Deactivate the product instead of deleting the record
                $p->update(['status' => 'inactive']);
            });

            $this->logAudit('product_delete', 'Producto desactivado.', [
                'product_id' => (int) $id,
                'name' => $productName,
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product deactivated successfully',
                ]);
            }

            return redirect()->route('inventory')->with('status', 'Product deactivated successfully');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deactivating product: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error deactivating product');
        }
    }

    public function forceDelete($id)
    {
        try {
            $productName = null;
            DB::transaction(function () use ($id, &$productName) {
                $p = Product::findOrFail($id);
                $productName = $p->name;
                $p->delete();
            });

            $this->logAudit('product_force_delete', 'Producto eliminado permanentemente.', [
                'product_id' => (int) $id,
                'name' => $productName,
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product permanently deleted',
                ]);
            }

            return redirect()->route('inventory')->with('status', 'Product permanently deleted');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting product: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error deleting product');
        }
    }

    public function create()
    {
        return view('products.create');
    }

    // Promote a gallery image to the main image collection
    public function promoteToMain(int $id, int $mediaId)
    {
        try {
            $product = Product::findOrFail($id);
            $mediaItem = $product->media()->where('id', $mediaId)->firstOrFail();

            // Copy the gallery file and register it as the main image
            $product->addMedia($mediaItem->getPath())
                ->preservingOriginal()
                ->toMediaCollection('main_image');

            return response()->json([
                'success' => true,
                'message' => 'Imagen promovida como principal.',
                'url' => $product->getFirstMediaUrl('main_image'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo promover la imagen.',
            ], 500);
        }
    }

    // Remove a single image from the gallery collection
    public function removeGalleryImage(int $id, int $mediaId)
    {
        try {
            $product = Product::findOrFail($id);
            $mediaItem = $product->media()->where('id', $mediaId)->firstOrFail();
            $mediaItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada de la galería.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar la imagen.',
            ], 500);
        }
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);

        return view('products.edit', compact('product'));
    }

    public function inventory(Request $request)
    {
        $query = $this->inventoryProductsFilteredQuery($request)->with(['category.parent', 'supplier']);

        $perPage = $request->get('per_page', 10);
        $paginator = $query->paginate($perPage);

        // Normalize products into the structure expected by the view
        $products = collect($paginator->items())->map(function (Product $product) {
            return (object) [
                'product_id' => $product->product_id,
                'id' => $product->product_id,
                'name' => $product->name,
                'sku' => Product::skuFromId((int) $product->product_id),
                'image' => $product->image ?? 'default.png',
                'category' => (object) ['name' => optional($product->category)->name ?? 'Uncategorized'],
                'stock' => $product->stock_current,
                'stock_status_class' => $product->adminInventoryStockBadgeClass(),
                'price' => $product->sale_price,
                'status' => ucfirst(str_replace('_', ' ', $product->status)),
                'status_class' => $product->status === 'active' ? 'success' :
                                ($product->status === 'inactive' ? 'warning' : 'secondary'),
            ];
        });

        // Load deduplicated root categories and the dependent subcategory tree
        $categories = Category::query()
            ->selectRaw('MIN(category_id) as category_id, name')
            ->whereNull('parent_category_id')
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $subcategoriesByParent = Category::subcategoriesGroupedByCanonicalParent();

        return view('admin.products.inventory', [
            'products' => $products,
            'paginator' => $paginator,
            'categories' => $categories,
            'subcategoriesByParent' => $subcategoriesByParent,
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'inventoryExportsQuery' => AdminInventoryExportQuery::queryStringFromRequest($request),
        ]);
    }

    public function export(Request $request, $format = null)
    {
        $format = strtolower($format ?? $request->get('format', 'pdf'));

        $exportAll = $request->query('scope') === 'all';
        $baseQuery = $exportAll ? $this->inventoryProductsFilteredQuery(new Request()) : $this->inventoryProductsFilteredQuery($request);
        $filterLines = $exportAll ? ['Inventario: todo (sin filtros)'] : $this->inventoryExportFilterLines($request);

        $withRelations = ['category:category_id,name', 'supplier:supplier_id,name'];

        if ($format === 'xml') {
            $data = (clone $baseQuery)->with($withRelations)->get();
            $xml = new \SimpleXMLElement('<products/>');
            foreach ($data as $p) {
                $n = $xml->addChild('product');
                $n->addChild('id', (string) $p->product_id);
                $n->addChild('name', htmlspecialchars($p->name));
                $n->addChild('description', htmlspecialchars($p->description ?? ''));
                $n->addChild('category', htmlspecialchars(optional($p->category)->name));
                $n->addChild('supplier', htmlspecialchars(optional($p->supplier)->name));
                $n->addChild('purchase_price', number_format((float) $p->purchase_price, 2, '.', ''));
                $n->addChild('sale_price', number_format((float) $p->sale_price, 2, '.', ''));
                $n->addChild('stock_current', (string) $p->stock_current);
                $n->addChild('stock_minimum', (string) $p->stock_minimum);
                $n->addChild('status', $p->status);
                $n->addChild('created_at', (string) $p->created_at);
            }
            $filename = 'products_'.date('Ymd_His').'.xml';

            return response($xml->asXML(), 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ]);
        }

        if ($format === 'pdf') {
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
            });

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

        if ($format === 'excel') {
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

            $headers = ['ID', 'Nombre', 'Descripción', 'Categoría', 'Proveedor', 'Precio compra', 'Precio venta', 'Stock actual', 'Stock mínimo', 'Estado', 'Creado'];
            $dataRows = $rows->map(function ($p) {
                return [
                    (string) $p->product_id,
                    $p->name,
                    $p->description ?? '',
                    optional($p->category)->name ?? '',
                    optional($p->supplier)->name ?? '',
                    number_format((float) $p->purchase_price, 2, '.', ''),
                    number_format((float) $p->sale_price, 2, '.', ''),
                    (string) $p->stock_current,
                    (string) $p->stock_minimum,
                    $p->status,
                    $p->created_at ? $p->created_at->format('Y-m-d H:i:s') : '',
                ];
            })->values()->all();

            return app(RegistryExcelExport::class)->download(
                'Inventario de productos',
                'Catálogo de inventario — Ciclo Finca 4',
                $headers,
                $dataRows,
                $excelFilterLines,
                ReportExcelFilename::make('inventario'),
            );
        }

        return response()->json([
            'success' => false,
            'message' => 'Formato no soportado. Use xml, pdf o excel.',
        ], 400);
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xml,csv,txt,json|max:10240',
        ]);

        $file = $request->file('import_file');

        $format = $this->detectFileFormat($file);

        if (! $format) {
            return redirect()->back()->with('error', 'No se pudo detectar el formato del archivo. Formatos soportados: XML, CSV y JSON.');
        }

        try {
            switch ($format) {
                case 'xml':
                    return $this->importXml($file);
                case 'csv':
                    return $this->importCsv($file);
                case 'json':
                    return $this->importJson($file);
                default:
                    return redirect()->back()->with('error', 'Formato no válido');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    private function detectFileFormat($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        if (in_array($extension, ['xml'])) {
            return 'xml';
        }
        if (in_array($extension, ['csv', 'txt'])) {
            return 'csv';
        }
        if (in_array($extension, ['json'])) {
            return 'json';
        }

        // Inspect the file content when the extension is ambiguous
        $content = file_get_contents($file->getPathname());
        $trimmedContent = trim($content);

        if (preg_match('/^<\?xml/i', $trimmedContent) || preg_match('/^<[a-zA-Z]/', $trimmedContent)) {
            return 'xml';
        }

        if (preg_match('/^[\s]*[\[\{]/', $trimmedContent)) {
            $decoded = json_decode($trimmedContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return 'json';
            }
        }

        if (in_array($extension, ['txt', 'csv']) || $mimeType === 'text/csv' || $mimeType === 'text/plain') {
            return 'csv';
        }

        return null;
    }

    private function importXml($file)
    {
        try {
            $xmlContent = file_get_contents($file->getPathname());

            if (empty(trim($xmlContent))) {
                throw new \Exception('El archivo XML está vacío o no se pudo leer correctamente.');
            }

            // Capture libxml parsing errors instead of emitting PHP warnings
            libxml_use_internal_errors(true);
            $xml = new \SimpleXMLElement($xmlContent);
            $xmlErrors = libxml_get_errors();

            if (! empty($xmlErrors)) {
                $errorMessages = array_map(function ($error) {
                    return trim($error->message);
                }, $xmlErrors);

                throw new \Exception('Error al parsear XML: '.implode(', ', $errorMessages));
            }

            $importados = 0;
            $errores = [];

            DB::beginTransaction();

            if (! isset($xml->producto) || count($xml->producto) == 0) {
                throw new \Exception('No se encontraron productos en el archivo XML.');
            }

            foreach ($xml->producto as $productoXml) {
                try {
                    $requiredFields = ['nombre', 'categoria', 'proveedor', 'precio_compra', 'precio_venta', 'stock_actual', 'stock_minimo'];
                    foreach ($requiredFields as $field) {
                        if (! isset($productoXml->$field)) {
                            throw new \Exception("Campo requerido '{$field}' no encontrado en el producto.");
                        }
                    }

                    $result = $this->createProductFromData([
                        'nombre' => (string) $productoXml->nombre,
                        'descripcion' => isset($productoXml->descripcion) ? (string) $productoXml->descripcion : '',
                        'categoria' => (string) $productoXml->categoria,
                        'proveedor' => (string) $productoXml->proveedor,
                        'precio_compra' => (float) $productoXml->precio_compra,
                        'precio_venta' => (float) $productoXml->precio_venta,
                        'stock_actual' => (int) $productoXml->stock_actual,
                        'stock_minimo' => (int) $productoXml->stock_minimo,
                        'estado' => isset($productoXml->estado) ? (string) $productoXml->estado : 'activo',
                    ]);

                    if ($result['success']) {
                        $importados++;
                    } else {
                        $errores[] = $result['error'];
                    }
                } catch (\Exception $e) {
                    $errores[] = 'Error al importar producto: '.$e->getMessage();
                }
            }

            // Roll back the full import if any record fails
            if (! empty($errores)) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            $this->logAudit(
                'products_import',
                'Importación de productos procesada (XML).',
                [
                    'format' => 'xml',
                    'imported' => $importados,
                    'errors_count' => count($errores),
                ]
            );

            return $this->handleImportResult($importados, $errores);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error al procesar el archivo XML: '.$e->getMessage());
        }
    }

    private function importCsv($file)
    {
        $csvData = array_map('str_getcsv', file($file->getPathname()));
        // Use the first row as the CSV header
        $headers = array_shift($csvData);
        $importados = 0;
        $errores = [];

        DB::beginTransaction();

        foreach ($csvData as $row) {
            try {
                $data = array_combine($headers, $row);
                $result = $this->createProductFromData($data);

                if ($result['success']) {
                    $importados++;
                } else {
                    $errores[] = $result['error'];
                }
            } catch (\Exception $e) {
                $errores[] = 'Error al importar producto: '.$e->getMessage();
            }
        }

        if (! empty($errores)) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        $this->logAudit(
            'products_import',
            'Importación de productos procesada (CSV).',
            [
                'format' => 'csv',
                'imported' => $importados,
                'errors_count' => count($errores),
            ]
        );

        return $this->handleImportResult($importados, $errores);
    }

    private function importJson($file)
    {
        $jsonContent = file_get_contents($file->getPathname());
        $data = json_decode($jsonContent, true);
        $importados = 0;
        $errores = [];

        DB::beginTransaction();

        foreach ($data as $productData) {
            try {
                $result = $this->createProductFromData($productData);

                if ($result['success']) {
                    $importados++;
                } else {
                    $errores[] = $result['error'];
                }
            } catch (\Exception $e) {
                $errores[] = 'Error al importar producto: '.$e->getMessage();
            }
        }

        if (! empty($errores)) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        $this->logAudit(
            'products_import',
            'Importación de productos procesada (JSON).',
            [
                'format' => 'json',
                'imported' => $importados,
                'errors_count' => count($errores),
            ]
        );

        return $this->handleImportResult($importados, $errores);
    }

    private function logAudit(string $actionType, string $description, array $meta = []): void
    {
        try {
            app(AuditLogger::class)->logAdminAction($actionType, 'products', $description, $meta);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed', [
                'action_type' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createProductFromData($data)
    {
        try {
            $category = Category::where('name', $data['categoria'])->first();
            if (! $category) {
                return ['success' => false, 'error' => 'Category not found: '.$data['categoria']];
            }

            $supplier = Supplier::where('name', $data['proveedor'])->first();
            if (! $supplier) {
                return ['success' => false, 'error' => 'Supplier not found: '.$data['proveedor']];
            }

            Product::create([
                'category_id' => $category->category_id,
                'supplier_id' => $supplier->supplier_id,
                'name' => $data['nombre'],
                'description' => $data['descripcion'] ?? '',
                'purchase_price' => $data['precio_compra'],
                'sale_price' => $data['precio_venta'],
                'stock_current' => $data['stock_actual'],
                'stock_minimum' => $data['stock_minimo'],
                // Map legacy imported status labels to internal enum values
                'status' => $this->mapLegacyStatus($data['estado'] ?? 'activo'),
            ]);

            return ['success' => true];
        } catch (ValidationException $e) {
            return ['success' => false, 'error' => $e->errors()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function mapLegacyStatus(string $status): string
    {
        // Map legacy Spanish status labels to internal English values
        return match (strtolower($status)) {
            'activo' => 'active',
            'inactivo' => 'inactive',
            'agotado' => 'out_of_stock',
            'descontinuado' => 'discontinued',
            default => $status,
        };
    }

    private function handleImportResult($importados, $errores)
    {
        $mensaje = "Se importaron {$importados} productos correctamente.";
        if (! empty($errores)) {
            $mensaje .= ' Errores: ';
            $formattedErrors = [];
            foreach ($errores as $error) {
                if (is_array($error)) {
                    foreach ($error as $fieldErrors) {
                        $formattedErrors[] = implode(', ', $fieldErrors);
                    }
                } else {
                    $formattedErrors[] = $error;
                }
            }
            // Limit the displayed errors to keep the flash message readable
            $mensaje .= implode('; ', array_slice($formattedErrors, 0, 5));
            if (count($formattedErrors) > 5) {
                $mensaje .= ' y '.(count($formattedErrors) - 5).' más...';
            }
        }

        return redirect()->route('inventory')->with('status', $mensaje);
    }

    // Build the filtered inventory query
    private function inventoryProductsFilteredQuery(Request $request): Builder
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('subcategory_id')) {
            $query->where('category_id', $request->subcategory_id);
        } elseif ($request->filled('parent_category_id')) {
            $canonicalParentId = (int) $request->parent_category_id;
            $physicalParentIds = Category::physicalRootIdsForCanonicalParent($canonicalParentId);
            $childIds = Category::whereIn('parent_category_id', $physicalParentIds)->pluck('category_id');
            $query->where(function ($q) use ($physicalParentIds, $childIds) {
                $q->whereIn('category_id', $physicalParentIds)
                    ->orWhereIn('category_id', $childIds);
            });
        } elseif ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in-stock':
                    // "En stock" = por encima del mínimo definido para el producto.
                    // Opción B: productos con stock_minimum = 0 no se incluyen aquí.
                    $query->where('stock_minimum', '>', 0)
                        ->whereColumn('stock_current', '>', 'stock_minimum');
                    break;
                case 'low':
                    // "Stock bajo" = stock positivo pero por debajo o igual al mínimo del producto.
                    // Opción B: productos con stock_minimum = 0 no se incluyen aquí.
                    $query->where('stock_minimum', '>', 0)
                        ->where('stock_current', '>', 0)
                        ->whereColumn('stock_current', '<=', 'stock_minimum');
                    break;
                case 'out':
                    $query->where('stock_current', 0);
                    break;
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        [$sort, $order] = $this->validatedInventorySort($request->get('sort'), $request->get('order'));
        $query->orderBy($sort, $order);

        return $query;
    }

    // Validate the inventory sort column and direction
    private function validatedInventorySort(mixed $sort, mixed $order): array
    {
        $allowed = ['product_id', 'name', 'stock_current', 'sale_price', 'status', 'category_id'];
        $col = is_string($sort) && in_array($sort, $allowed, true) ? $sort : 'product_id';
        $dir = is_string($order) && strtolower($order) === 'asc' ? 'asc' : 'desc';

        return [$col, $dir];
    }

    // Build human-readable export filter lines
    private function inventoryExportFilterLines(Request $request): array
    {
        $lines = [];

        if ($request->filled('search')) {
            $lines[] = 'Búsqueda: '.$request->search;
        }
        if ($request->filled('subcategory_id')) {
            $sub = Category::find($request->subcategory_id);
            $lines[] = 'Subcategoría: '.($sub !== null ? $sub->name : '#'.$request->subcategory_id);
        } elseif ($request->filled('parent_category_id')) {
            $canonicalParentId = (int) $request->parent_category_id;
            $roots = Category::physicalRootIdsForCanonicalParent($canonicalParentId);
            $label = Category::whereIn('category_id', $roots)->value('name');
            $lines[] = 'Categoría: '.($label ?? 'ID '.$canonicalParentId);
        } elseif ($request->filled('category_id')) {
            $cat = Category::find($request->category_id);
            $lines[] = 'Categoría (ID): '.($cat !== null ? $cat->name : '#'.$request->category_id);
        }

        if ($request->filled('stock_status')) {
            $lines[] = 'Stock: '.match ($request->stock_status) {
                'in-stock' => 'En stock',
                'low' => 'Stock bajo',
                'out' => 'Sin stock',
                default => (string) $request->stock_status,
            };
        }

        if ($request->filled('status')) {
            $lines[] = 'Estado producto: '.$request->status;
        }

        if (count($lines) === 0) {
            $lines[] = 'Sin filtros adicionales (todos los productos según orden por defecto).';
        }

        return $lines;
    }

    // Add manual stock and register the inventory movement
    public function addManualStock(Request $request, int $id, InventoryMovementService $inventoryService)
    {
        $validReasons = ['manual_adjustment', 'damage', 'refund'];

        try {
            $validated = $request->validate([
                'quantity' => ['required', 'numeric', 'min:1'],
                'reason' => ['required', 'string', 'in:'.implode(',', $validReasons)],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Datos inválidos.',
            ], 422);
        }

        try {
            $product = Product::findOrFail($id);

            $inventoryService->recordManualEntry(
                product: $product,
                quantity: (int) $validated['quantity'],
                reason: $validated['reason'],
            );

            return response()->json([
                'success' => true,
                'message' => "Se agregaron {$validated['quantity']} unidades correctamente.",
                'stock_current' => $product->stock_current,
            ]);

        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);

        } catch (\Throwable $e) {
            Log::error('addManualStock error', ['product_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al actualizar el stock. Inténtalo de nuevo.',
            ], 500);
        }
    }

    // Remove manual stock and register the inventory movement
    public function removeManualStock(Request $request, int $id, InventoryMovementService $inventoryService)
    {
        $validReasons = ['manual_adjustment', 'damage', 'refund'];

        try {
            $validated = $request->validate([
                'quantity' => ['required', 'numeric', 'min:1'],
                'reason' => ['required', 'string', 'in:'.implode(',', $validReasons)],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Datos inválidos.',
            ], 422);
        }

        try {
            $product = Product::findOrFail($id);

            $inventoryService->recordManualExit(
                product: $product,
                quantity: (int) $validated['quantity'],
                reason: $validated['reason'],
            );

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$validated['quantity']} unidades correctamente.",
                'stock_current' => $product->stock_current,
            ]);

        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);

        } catch (\Throwable $e) {
            Log::error('removeManualStock error', ['product_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al actualizar el stock. Inténtalo de nuevo.',
            ], 500);
        }
    }
}
