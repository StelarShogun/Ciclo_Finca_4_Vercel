<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Services\Admin\AdminInventoryExportQuery;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\AdminPdfExportService;
use App\Services\Admin\Images\ProductImageOptimizerService;
use App\Services\Admin\RegistryExcelExport;
use App\Services\Admin\ReportExcelFilename;
use App\Services\AuditLogger;
use App\Services\InventoryMovementService;
use App\Services\ProductClassificationAssignmentService;
use App\Support\AdminPerPage;
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
            $perPage = AdminPerPage::resolve($request->get('per_page', 10));
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
            $slug = $this->productImageSlug($product);
            $folderPath = $this->productImageFolderPath($product);

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $ext = $file->extension() ?: $file->getClientOriginalExtension();
                $filename = $slug.'_main.'.$ext;
                $file->move($folderPath, $filename);
                $this->addSanitizedMedia($product, $folderPath.'/'.$filename, 'main_image');
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
                    $this->addSanitizedMedia($product, $folderPath.'/'.$filename, 'gallery');
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
            $product = Product::with(['category.parent', 'supplier', 'brands', 'classificationValues.dimension', 'variants'])->findOrFail($id);

            if (request()->wantsJson() || request()->ajax()) {
                $productData = $product->toArray();
                $firstBrand = $product->brands->first();
                $productData['brand_id'] = $firstBrand instanceof Brand ? $firstBrand->id : null;
                $productData['classification_value_ids'] = $product->classificationValues->pluck('id')->values()->all();
                $productData['media_main'] = $product->getFirstMediaUrl('main_image');
                $productData['media_gallery'] = $product->getMedia('gallery')->map(fn ($m) => $m->getUrl())->values()->toArray();
                $variantIds = $product->variants->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

                $lockedVariantIds = [];
                if ($variantIds !== []) {
                    $lockedVariantIds = SaleItem::query()
                        ->whereIn('product_id', $variantIds)
                        ->distinct()
                        ->pluck('product_id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                }
                $lockedSet = array_fill_keys($lockedVariantIds, true);

                $productData['variants'] = $product->variants
                    ->map(function (Product $v) use ($lockedSet) {
                        return [
                            'product_id' => (int) $v->product_id,
                            'name' => (string) $v->name,
                            'status' => (string) $v->status,
                            'stock_current' => (int) $v->stock_current,
                            'sale_price' => (string) $v->sale_price,
                            'sku' => $v->displaySku(),
                            'sku_custom' => $v->sku,
                            'sku_locked' => isset($lockedSet[(int) $v->product_id]),
                        ];
                    })
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
            $slug = $this->productImageSlug($product);
            $folderPath = $this->productImageFolderPath($product);

            // Remove main image when requested (no replacement file)
            if ($request->boolean('remove_main_image') && ! $request->hasFile('image')) {
                foreach (glob($folderPath.'/'.$slug.'_main.*') ?: [] as $old) {
                    @unlink($old);
                }
                $product->clearMediaCollection('main_image');
            }

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
                $this->addSanitizedMedia($product, $folderPath.'/'.$filename, 'main_image');
            }

            // Replace the entire gallery when new files are provided
            if ($request->hasFile('images')) {
                // Remove existing gallery files from disk
                foreach (glob($folderPath.'/'.$slug.'_[0-9]*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [] as $old) {
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
                    $this->addSanitizedMedia($product, $folderPath.'/'.$filename, 'gallery');
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
        $lowStockProductsCount = Product::query()->lowStockAlert()->count();
        $hasClassificationSelections = collect((array) $request->input('classifications', []))
            ->contains(fn ($value) => is_string($value) && trim($value) !== '');
        $classificationFilters = $hasClassificationSelections
            ? $this->inventoryClassificationFilters($request)
            : [];

        $perPage = AdminPerPage::resolve($request->get('per_page', 10));
        $paginator = $query->paginate($perPage)->withQueryString();

        // Normalize products into the structure expected by the view
        $products = collect($paginator->items())->map(function ($product) {
            if (! $product instanceof Product) {
                return null;
            }

            return (object) [
                'product_id' => $product->product_id,
                'id' => $product->product_id,
                'name' => $product->name,
                'sku' => $product->displaySku(),
                'image' => $product->image ?? 'default.png',
                'category' => (object) ['name' => optional($product->category)->name ?? 'Uncategorized'],
                'stock' => $product->stock_current,
                'stock_status_class' => $product->adminInventoryStockBadgeClass(),
                'price' => $product->sale_price,
                'status' => ucfirst(str_replace('_', ' ', $product->status)),
                'status_class' => $product->status === 'active' ? 'success' :
                                ($product->status === 'inactive' ? 'warning' : 'secondary'),
            ];
        })->filter();

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
            'lowStockProductsCount' => $lowStockProductsCount,
            'categories' => $categories,
            'subcategoriesByParent' => $subcategoriesByParent,
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['supplier_id', 'name']),
            'inventoryExportsQuery' => AdminInventoryExportQuery::queryStringFromRequest($request),
            'classificationFilters' => $classificationFilters,
            'hasClassificationSelections' => $hasClassificationSelections,
        ]);
    }

    public function inventoryClassificationFiltersOptions(Request $request)
    {
        return response()->json([
            'success' => true,
            'filters' => $this->inventoryClassificationFilters($request),
        ]);
    }

    public function export(Request $request, $format = null)
    {
        $format = strtolower($format ?? $request->get('format', 'pdf'));

        $exportAll = $request->query('scope') === 'all';
        $baseQuery = $exportAll ? $this->inventoryProductsFilteredQuery(new Request) : $this->inventoryProductsFilteredQuery($request);
        $filterLines = $exportAll ? ['Inventario: todo (sin filtros)'] : $this->inventoryExportFilterLines($request);

        $withRelations = ['category:category_id,name', 'supplier:supplier_id,name'];

        if ($format === 'xml') {
            $data = (clone $baseQuery)->with($withRelations)->get();
            $xml = new \SimpleXMLElement('<products/>');
            foreach ($data as $p) {
                if (! $p instanceof Product) {
                    continue;
                }

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
                if (! $p instanceof Product) {
                    return null;
                }

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

        return response()->json([
            'success' => false,
            'message' => 'Formato no soportado. Use xml, pdf o excel.',
        ], 400);
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

        $classificationFilters = $request->input('classifications', []);
        if (is_array($classificationFilters)) {
            foreach ($classificationFilters as $slug => $rawValue) {
                $slug = trim((string) $slug);
                if ($slug === '' || ! is_string($rawValue) || trim($rawValue) === '') {
                    continue;
                }
                $normalizedValue = ClassificationValue::normalizeStoredValue($rawValue);
                $query->whereHas('classificationValues', function ($q) use ($slug, $normalizedValue) {
                    $q->where('classification_values.normalized_value', $normalizedValue)
                        ->whereHas('dimension', fn ($d) => $d->where('slug', $slug));
                });
            }
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
        $classificationFilters = $request->input('classifications', []);
        if (is_array($classificationFilters)) {
            $labelsBySlug = ClassificationDimension::query()
                ->whereIn('slug', array_keys($classificationFilters))
                ->pluck('label', 'slug');

            foreach ($classificationFilters as $slug => $value) {
                if (! is_string($value) || trim($value) === '') {
                    continue;
                }
                $label = $labelsBySlug[$slug] ?? $slug;
                $lines[] = "{$label}: {$value}";
            }
        }

        if (count($lines) === 0) {
            $lines[] = 'Sin filtros adicionales (todos los productos según orden por defecto).';
        }

        return $lines;
    }

    /** Dynamic classification filters loaded on demand for the current inventory scope. */
    private function inventoryClassificationFilters(?Request $request = null): array
    {
        $requestForScope = $request ?? new Request;
        $requestWithoutClassification = $requestForScope->duplicate();
        $requestWithoutClassification->merge(['classifications' => []]);
        $filteredProductIds = $this->inventoryProductsFilteredQuery($requestWithoutClassification)
            ->reorder()
            ->select('products.product_id');

        $dimensions = ClassificationDimension::query()
            ->select(['slug', 'label'])
            ->join('classification_product', 'classification_product.classification_dimension_id', '=', 'classification_dimensions.id')
            ->joinSub(clone $filteredProductIds, 'inventory_filtered_products', function ($join) {
                $join->on('inventory_filtered_products.product_id', '=', 'classification_product.product_id');
            })
            ->whereNull('classification_dimensions.deleted_at')
            ->groupBy('classification_dimensions.slug', 'classification_dimensions.label')
            ->orderBy('label')
            ->get();

        return $dimensions->map(function (ClassificationDimension $dimension) use ($filteredProductIds) {
            return [
                'slug' => (string) $dimension->slug,
                'label' => (string) $dimension->label,
                'options' => $this->classificationFilterValuesBySlug((string) $dimension->slug, clone $filteredProductIds),
            ];
        })->filter(fn (array $f) => $f['options'] !== [])->values()->all();
    }

    /** Distinct visible values for a classification slug across products. */
    private function classificationFilterValuesBySlug(string $slug, ?Builder $filteredProductIds = null): array
    {
        $query = ClassificationValue::query()
            ->selectRaw('classification_values.normalized_value, MIN(classification_values.value) AS display_value')
            ->join('classification_dimensions', 'classification_dimensions.id', '=', 'classification_values.classification_dimension_id')
            ->join('classification_product', 'classification_product.classification_value_id', '=', 'classification_values.id')
            ->join('products', 'products.product_id', '=', 'classification_product.product_id')
            ->where('classification_dimensions.slug', $slug)
            ->whereNull('classification_dimensions.deleted_at')
            ->whereNull('classification_values.deleted_at')
            ->groupBy('classification_values.normalized_value')
            ->orderBy('display_value');

        if ($filteredProductIds !== null) {
            $query->joinSub($filteredProductIds, 'inventory_filtered_products', function ($join) {
                $join->on('inventory_filtered_products.product_id', '=', 'classification_product.product_id');
            });
        }

        return $query->get()
            ->map(fn ($row) => [
                'value' => (string) $row->normalized_value,
                'label' => (string) $row->display_value,
            ])
            ->values()
            ->all();
    }

    // Add manual stock and register the inventory movement
    public function addManualStock(Request $request, int $id, InventoryMovementService $inventoryService)
    {
        try {
            $validated = $request->validate([
                'quantity' => ['required', 'numeric', 'min:1'],
                'reason' => ['required', 'string', 'min:3', 'max:500'],
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
        try {
            $validated = $request->validate([
                'quantity' => ['required', 'numeric', 'min:1'],
                'reason' => ['required', 'string', 'min:3', 'max:500'],
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

    protected function productImageSlug(Product $product): string
    {
        return Str::slug($product->name, '_');
    }

    protected function productImageFolderPath(Product $product): string
    {
        $folderPath = public_path('images/'.$this->productImageSlug($product));

        if (! is_dir($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        return $folderPath;
    }

    protected function addSanitizedMedia(Product $product, string $absolutePath, string $collection): void
    {
        $optimizer = app(ProductImageOptimizerService::class);
        $field = $collection === 'main_image' ? 'image' : 'images';

        try {
            $sanitizedPath = $optimizer->sanitizePath($absolutePath);
        } catch (\Throwable $e) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            Log::warning('cf4_image_sanitize_failed', [
                'path' => $absolutePath,
                'collection' => $collection,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                $field => ['No se pudo procesar la imagen de forma segura. Usá JPEG, PNG, GIF o WebP.'],
            ]);
        }

        $product->addMedia($sanitizedPath)
            ->preservingOriginal()
            ->toMediaCollection($collection);
    }
}
