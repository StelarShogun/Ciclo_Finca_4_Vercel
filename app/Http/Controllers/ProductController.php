<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Admin\AdminInventoryExportQuery;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\ReportPdfFilename;
use App\Services\ProductClassificationAssignmentService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Database\Eloquent\Builder;
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
            $product = DB::transaction(function () use ($request) {
                $data = $request->validated();
                $brandId = $data['brand_id'];
                unset($data['brand_id']);
                $classificationIds = $data['classification_value_ids'] ?? [];
                unset($data['classification_value_ids']);

                // File fields are handled by MediaLibrary after the product is created
                unset($data['image'], $data['images']);

                $product = Product::create($data);
                $product->brands()->attach($brandId);
                app(ProductClassificationAssignmentService::class)->syncForProduct($product, $classificationIds);

                return $product;
            });

            // Save images to public/images/{product_name}/ then register with MediaLibrary
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
            $product = Product::with(['category.parent', 'supplier', 'brands', 'classificationValues'])->findOrFail($id);

            if (request()->wantsJson() || request()->ajax()) {
                $productData = $product->toArray();
                $firstBrand = $product->brands->first();
                $productData['brand_id'] = $firstBrand instanceof Brand ? $firstBrand->id : null;
                $productData['classification_value_ids'] = $product->classificationValues->pluck('id')->values()->all();
                $productData['media_main'] = $product->getFirstMediaUrl('main_image');
                $productData['media_gallery'] = $product->getMedia('gallery')->map(fn ($m) => $m->getUrl())->values()->toArray();

                return response()->json([
                    'success' => true,
                    'data' => $productData,
                ]);
            }

            return view('products.show', compact('product'));
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado',
                    'error' => $e->getMessage(),
                ], 404);
            }

            return redirect()->route('inventory')->with('error', 'Producto no encontrado');
        }
    }

    public function toggleFeatured(int $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->is_featured = ! $product->is_featured;
            $product->save();

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
            $product = DB::transaction(function () use ($request, $id) {
                $p = Product::findOrFail($id);
                $data = $request->validated();
                $brandId = $data['brand_id'];
                unset($data['brand_id']);
                $syncClassifications = $request->has('classification_value_ids');
                $classificationIds = $syncClassifications ? ($data['classification_value_ids'] ?? []) : null;
                unset($data['classification_value_ids']);

                // File fields are handled by MediaLibrary after the product is updated
                unset($data['image'], $data['images']);

                $p->update($data);
                $p->brands()->sync([$brandId]);
                $p->refresh();
                if ($syncClassifications) {
                    app(ProductClassificationAssignmentService::class)->syncForProduct($p, $classificationIds ?? []);
                }

                return $p;
            });

            // Save images to public/images/{product_name}/ then register with MediaLibrary
            $folderPath = public_path('images/'.$product->name);
            if (! is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $slug = Str::slug($product->name, '_');

            // Replace main image when a new one is uploaded (singleFile handles deletion of the old one)
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $ext = $file->extension() ?: $file->getClientOriginalExtension();
                // Remove any existing main file for this product
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
                // Remove existing numbered gallery files from the folder
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
            DB::transaction(function () use ($id) {
                $p = Product::findOrFail($id);
                // Soft-delete by marking inactive rather than removing the record
                $p->update(['status' => 'inactive']);
            });

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
            DB::transaction(function () use ($id) {
                $p = Product::findOrFail($id);
                $p->delete();
            });

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

    /**
     * Promote a gallery image to the main_image collection (replaces the current main image).
     * POST /products/{id}/gallery/{mediaId}/promote
     */
    public function promoteToMain(int $id, int $mediaId)
    {
        try {
            $product = Product::findOrFail($id);
            $mediaItem = $product->media()->where('id', $mediaId)->firstOrFail();

            // Copy the file preserving the gallery item, then set as single main image
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

    /**
     * Remove a single image from the gallery collection.
     * DELETE /products/{id}/gallery/{mediaId}
     */
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

        // Normalize raw Eloquent models into a consistent shape expected by the view
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

        // Raíces deduplicadas por nombre (filtro "Categoría") + árbol para selects dependientes en JS
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
        $format = strtolower($format ?? $request->get('format', 'csv'));

        $baseQuery = $this->inventoryProductsFilteredQuery($request);
        $filterLines = $this->inventoryExportFilterLines($request);

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

        if ($format === 'json') {
            $data = (clone $baseQuery)->with($withRelations)->get();
            $payload = $data->map(function ($p) {
                return [
                    'id' => $p->product_id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'category' => optional($p->category)->name,
                    'supplier' => optional($p->supplier)->name,
                    'purchase_price' => $p->purchase_price,
                    'sale_price' => $p->sale_price,
                    'stock_current' => $p->stock_current,
                    'stock_minimum' => $p->stock_minimum,
                    'status' => $p->status,
                    'created_at' => $p->created_at,
                ];
            });
            $filename = 'products_'.date('Ymd_His').'.json';

            return response()->streamDownload(function () use ($payload) {
                echo $payload->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
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

            $pdf = PDF::loadView('admin.products.products-pdf', [
                'products' => $products,
                'total' => $products->count(),
                'totalMatching' => $totalMatching,
                'fecha_exportacion' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
                'pdfTitle' => 'Reporte de inventario',
                'pdfSubtitle' => 'Productos filtrados — Ciclo Finca 4',
                'logoPath' => is_file($logoPath) ? $logoPath : null,
                'filterLines' => $pdfFilterLines,
                'generatedFor' => 'Administración',
            ]);

            return $pdf->download(ReportPdfFilename::make('inventario'));
        }

        // Default to CSV export (streaming por chunks: no cargar todo el inventario en memoria).
        $filename = 'products_'.date('Ymd_His').'.csv';
        $chunk = AdminPdfExportLimits::INVENTORY_CSV_CHUNK;

        return response()->streamDownload(function () use ($baseQuery, $withRelations, $chunk): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for correct Excel rendering
            fputcsv($out, ['ID', 'Name', 'Description', 'Image', 'Category', 'Supplier', 'Purchase Price', 'Sale Price', 'Stock', 'Minimum', 'Status', 'Created']);
            (clone $baseQuery)
                ->with($withRelations)
                ->orderBy('product_id')
                ->chunkById($chunk, function ($products) use ($out): void {
                    foreach ($products as $p) {
                        fputcsv($out, [
                            $p->product_id,
                            $p->name,
                            $p->description,
                            $p->image ?? '',
                            optional($p->category)->name,
                            optional($p->supplier)->name,
                            $p->purchase_price,
                            $p->sale_price,
                            $p->stock_current,
                            $p->stock_minimum,
                            $p->status,
                            $p->created_at ? $p->created_at->format('Y-m-d H:i:s') : '',
                        ]);
                    }
                }, 'product_id');
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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

        // Fall back to content sniffing when the extension is ambiguous
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

            // Capture libxml errors internally instead of emitting PHP warnings
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

            // Roll back the entire import if any record failed to keep the dataset consistent
            if (! empty($errores)) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return $this->handleImportResult($importados, $errores);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error al procesar el archivo XML: '.$e->getMessage());
        }
    }

    private function importCsv($file)
    {
        $csvData = array_map('str_getcsv', file($file->getPathname()));
        // First row is treated as the header to build associative arrays per product
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

        return $this->handleImportResult($importados, $errores);
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
                // Translate Spanish status values from imported files to internal English enums
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
        // Maps Spanish status labels used in import files to the internal English enum values
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
            // Cap the displayed error list at 5 to keep the flash message readable
            $mensaje .= implode('; ', array_slice($formattedErrors, 0, 5));
            if (count($formattedErrors) > 5) {
                $mensaje .= ' y '.(count($formattedErrors) - 5).' más...';
            }
        }

        return redirect()->route('inventory')->with('status', $mensaje);
    }

    /**
     * @return Builder<Product>
     */
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
                    $query->where('stock_current', '>', Product::CLIENT_LOW_STOCK_THRESHOLD);
                    break;
                case 'low':
                    $query->where('stock_current', '>', 0)
                        ->where('stock_current', '<=', Product::CLIENT_LOW_STOCK_THRESHOLD);
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

    /**
     * @return array{0: string, 1: string}
     */
    private function validatedInventorySort(mixed $sort, mixed $order): array
    {
        $allowed = ['product_id', 'name', 'stock_current', 'sale_price', 'status', 'category_id'];
        $col = is_string($sort) && in_array($sort, $allowed, true) ? $sort : 'product_id';
        $dir = is_string($order) && strtolower($order) === 'asc' ? 'asc' : 'desc';

        return [$col, $dir];
    }

    /**
     * @return array<int, string>
     */
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

     /**
     * POST /inventory/add-manual/{id}
     * Manually add stock to a product.
     */
    public function addManualStock(Request $request, int $id)
    {
        $validReasons = ['manual_adjustment', 'damage', 'refund'];
 
        try {
            $validated = $request->validate([
                'quantity' => ['required', 'numeric', 'min:1'],
                'reason'   => ['required', 'string', 'in:' . implode(',', $validReasons)],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
                'message' => 'Datos inválidos.',
            ], 422);
        }
 
        try {
            $product = DB::transaction(function () use ($id, $validated) {
                /** @var \App\Models\Product $product */
                $product = \App\Models\Product::lockForUpdate()->findOrFail($id);
 
                $product->stock_current += (int) $validated['quantity'];
                $product->save();
 
                return $product;
            });
 
            return response()->json([
                'success'       => true,
                'message'       => "Se agregaron {$validated['quantity']} unidades correctamente.",
                'stock_current' => $product->stock_current,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            // Triggered by the Product model's booted() saving hook
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
                'message' => 'No se pudo actualizar el stock: ' . collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('addManualStock error', ['product_id' => $id, 'error' => $e->getMessage()]);
 
            return response()->json([
                'success' => false,
                'message' => 'Error interno al actualizar el stock. Inténtalo de nuevo.',
            ], 500);
        }
    }
 
    /**
     * POST /inventory/remove-manual/{id}
     * Manually remove stock from a product.
     */
    public function removeManualStock(Request $request, int $id)
    {
        $validReasons = ['manual_adjustment', 'damage', 'refund'];
 
        try {
            $validated = $request->validate([
                'quantity' => ['required', 'numeric', 'min:1'],
                'reason'   => ['required', 'string', 'in:' . implode(',', $validReasons)],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
                'message' => 'Datos inválidos.',
            ], 422);
        }
 
        try {
            $product = DB::transaction(function () use ($id, $validated) {
                /** @var \App\Models\Product $product */
                $product = \App\Models\Product::lockForUpdate()->findOrFail($id);
 
                $qty = (int) $validated['quantity'];
 
                if ($qty > $product->stock_current) {
                    throw ValidationException::withMessages([
                        'quantity' => [
                            "La cantidad ({$qty}) supera el stock disponible ({$product->stock_current}).",
                        ],
                    ]);
                }
 
                $product->stock_current -= $qty;
                $product->save();
 
                return $product;
            });
 
            return response()->json([
                'success'       => true,
                'message'       => "Se eliminaron {$validated['quantity']} unidades correctamente.",
                'stock_current' => $product->stock_current,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
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
