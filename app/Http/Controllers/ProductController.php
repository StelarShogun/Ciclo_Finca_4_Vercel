<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Products\CreateProduct;
use App\Actions\Admin\Products\ToggleProductFeatured;
use App\Actions\Admin\Products\UpdateProduct;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Services\Admin\Products\AdminInventoryProductQuery;
use App\Services\Admin\Products\ProductAuditLogger;
use App\Support\AdminPerPage;
use App\Support\ClientStorefrontCache;
use App\Support\ProductImageUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        private ProductAuditLogger $productAudit,
        private AdminInventoryProductQuery $inventoryProductQuery,
    ) {}

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

    public function store(StoreProductRequest $request, CreateProduct $createProduct)
    {
        try {
            $result = $createProduct->handle($request);
            $product = $result->product;

            $this->productAudit->log('product_create', 'Producto creado.', $result->auditContext);
            ClientStorefrontCache::forgetAfterProductMutation();

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
            Log::error('Product store failed.', [
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

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
                $productData['uses_placeholder_image'] = ProductImageUrls::usesPlaceholder($product);
                $productData['placeholder_icon_class'] = ProductImageUrls::placeholderIconClass($product);
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

    public function toggleFeatured(int $id, ToggleProductFeatured $toggleProductFeatured)
    {
        try {
            $payload = $toggleProductFeatured->handle($id);

            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el destacado. Inténtalo de nuevo.',
            ], 500);
        }
    }

    public function update(UpdateProductRequest $request, $id, UpdateProduct $updateProduct)
    {
        try {
            $result = $updateProduct->handle($request, $id);
            $product = $result->product;

            $this->productAudit->log('product_update', 'Producto actualizado.', $result->auditContext);
            ClientStorefrontCache::forgetAfterProductMutation();

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
            $product = Product::findOrFail($id);

            if ($product->status === 'inactive') {
                if (request()->wantsJson() || request()->ajax()) {
                    return response()->json([
                        'success' => true,
                        'already_inactive' => true,
                        'message' => 'Product is already inactive',
                        'status' => 'inactive',
                    ]);
                }

                return redirect()->route('inventory')->with('status', 'Product is already inactive');
            }

            $productName = $product->name;
            DB::transaction(function () use ($product) {
                $product->update(['status' => 'inactive']);
            });

            $this->logAudit('product_delete', 'Producto desactivado.', [
                'product_id' => (int) $id,
                'name' => $productName,
            ]);
            ClientStorefrontCache::forgetAfterProductMutation();

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product deactivated successfully',
                    'status' => 'inactive',
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

    public function activate($id)
    {
        try {
            $product = Product::findOrFail($id);
            $productName = $product->name;
            $wasActive = $product->status === 'active';

            DB::transaction(function () use ($product) {
                $product->update(['status' => 'active']);
            });

            if (! $wasActive) {
                $this->logAudit('product_activate', 'Producto reactivado.', [
                    'product_id' => (int) $id,
                    'name' => $productName,
                ]);
                ClientStorefrontCache::forgetAfterProductMutation();
            }

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'already_active' => $wasActive,
                    'message' => $wasActive ? 'Product is already active' : 'Product activated successfully',
                    'status' => 'active',
                ]);
            }

            return redirect()->route('inventory')->with('status', 'Product activated successfully');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error activating product: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error activating product');
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
            ClientStorefrontCache::forgetAfterProductMutation();

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

    public function edit($id)
    {
        $product = Product::findOrFail($id);

        return view('products.edit', compact('product'));
    }

    public function inventory(Request $request)
    {
        $query = $this->inventoryProductQuery->filteredQuery($request)->with(['category.parent', 'supplier']);
        $lowStockProductsCount = Product::query()->lowStockAlert()->count();
        $hasClassificationSelections = collect((array) $request->input('classifications', []))
            ->contains(fn ($value) => is_string($value) && trim($value) !== '');
        $activeClassificationFilters = $hasClassificationSelections
            ? $this->inventoryActiveClassificationFilters($request)
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
            'activeClassificationFilters' => $activeClassificationFilters,
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

    public function inventoryClassificationFilterDimensions(Request $request)
    {
        return response()->json([
            'success' => true,
            'dimensions' => collect($this->inventoryClassificationFilters($request))
                ->map(fn (array $filter) => [
                    'slug' => $filter['slug'],
                    'label' => $filter['label'],
                ])
                ->values(),
        ]);
    }

    public function inventoryClassificationFilterSuggest(Request $request, string $slug)
    {
        $requestForScope = $request->duplicate();
        $classifications = (array) $request->input('classifications', []);
        unset($classifications[$slug]);
        $requestForScope->merge(['classifications' => $classifications]);

        $filteredProductIds = $this->inventoryProductQuery->filteredQuery($requestForScope)
            ->reorder()
            ->select('products.product_id');

        $search = trim((string) $request->get('q', ''));
        $limit = max(1, min(50, (int) $request->get('limit', 50)));

        return response()->json([
            'success' => true,
            'options' => $this->classificationFilterValuesBySlug($slug, clone $filteredProductIds, $search, $limit),
        ]);
    }

    private function logAudit(string $actionType, string $description, array $meta = []): void
    {
        $this->productAudit->log($actionType, $description, $meta);
    }

    /** Dynamic classification filters loaded on demand for the current inventory scope. */
    private function inventoryClassificationFilters(?Request $request = null): array
    {
        $requestForScope = $request ?? new Request;
        $requestWithoutClassification = $requestForScope->duplicate();
        $requestWithoutClassification->merge(['classifications' => []]);
        $filteredProductIds = $this->inventoryProductQuery->filteredQuery($requestWithoutClassification)
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

    /** Active classification filters with display labels for inventory chips. */
    private function inventoryActiveClassificationFilters(Request $request): array
    {
        $classifications = collect($request->input('classifications', []))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '');

        if ($classifications->isEmpty()) {
            return [];
        }

        $dimensions = ClassificationDimension::query()
            ->whereIn('slug', $classifications->keys())
            ->pluck('label', 'slug');

        $result = [];
        foreach ($classifications as $slug => $normalizedValue) {
            $slug = (string) $slug;
            $normalizedValue = (string) $normalizedValue;

            $displayValue = ClassificationValue::query()
                ->selectRaw('MIN(classification_values.value) AS display_value')
                ->join('classification_dimensions', 'classification_dimensions.id', '=', 'classification_values.classification_dimension_id')
                ->where('classification_dimensions.slug', $slug)
                ->where('classification_values.normalized_value', $normalizedValue)
                ->whereNull('classification_dimensions.deleted_at')
                ->whereNull('classification_values.deleted_at')
                ->value('display_value');

            $result[] = [
                'slug' => $slug,
                'dimension_label' => (string) ($dimensions[$slug] ?? $slug),
                'value' => $normalizedValue,
                'value_label' => (string) ($displayValue ?? $normalizedValue),
            ];
        }

        return $result;
    }

    /** Distinct visible values for a classification slug across products. */
    private function classificationFilterValuesBySlug(
        string $slug,
        ?Builder $filteredProductIds = null,
        ?string $search = null,
        ?int $limit = null
    ): array {
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

        if ($search !== null && trim($search) !== '') {
            $term = '%'.addcslashes(trim($search), '%_\\').'%';
            $query->where('classification_values.value', 'LIKE', $term);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn ($row) => [
                'value' => (string) $row->normalized_value,
                'label' => (string) $row->display_value,
            ])
            ->values()
            ->all();
    }
}
