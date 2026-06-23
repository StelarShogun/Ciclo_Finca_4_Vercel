<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Admin\AdminInventoryExportQuery;
use App\Services\Admin\Products\AdminInventoryProductQuery;
use App\Services\Admin\Products\InventoryClassificationFilterService;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Services\Media\ProductImageUrls;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ProductInventoryController extends Controller
{
    public function __construct(
        private AdminInventoryProductQuery $inventoryProductQuery,
        private InventoryClassificationFilterService $classificationFilters,
    ) {}

    public function index(Request $request)
    {
        $query = $this->inventoryProductQuery->filteredQuery($request)->with(['category.parent', 'supplier']);
        $lowStockProductsCount = Product::query()->lowStockAlert()->count();
        $hasClassificationSelections = collect((array) $request->input('classifications', []))
            ->contains(fn ($value) => is_string($value) && trim($value) !== '');
        $activeClassificationFilters = $hasClassificationSelections
            ? $this->classificationFilters->activeFilters($request)
            : [];

        $perPage = AdminPerPage::resolve($request->get('per_page', 10));
        $paginator = $query->paginate($perPage)->withQueryString();

        $products = collect($paginator->items())->map(function ($product) {
            if (! $product instanceof Product) {
                return null;
            }

            return [
                'product_id' => (int) $product->product_id,
                'name' => $product->name,
                'sku' => $product->displaySku(),
                // fallbackUrl/usesPlaceholder están protegidos contra el disco read-only de Vercel.
                'image_url' => ProductImageUrls::fallbackUrl($product),
                'uses_placeholder' => ProductImageUrls::usesPlaceholder($product),
                'placeholder_icon' => ProductImageUrls::placeholderIconClass($product),
                'category_name' => optional($product->category)->name ?? 'Sin categoría',
                'stock' => (int) $product->stock_current,
                'stock_minimum' => (int) $product->stock_minimum,
                'stock_badge_class' => $product->adminInventoryStockBadgeClass(),
                'availability_label' => $product->adminAvailabilityLabel(),
                'price' => $product->sale_price,
                'status' => $product->status,
                'status_label' => ucfirst(str_replace('_', ' ', $product->status)),
                'status_class' => $product->status === 'active' ? 'success' :
                                ($product->status === 'inactive' ? 'warning' : 'secondary'),
                'is_featured' => (bool) $product->is_featured,
            ];
        })->filter()->values()->all();

        $categories = Category::query()
            ->selectRaw('MIN(category_id) as category_id, name')
            ->whereNull('parent_category_id')
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $subcategoriesByParent = Category::subcategoriesGroupedByCanonicalParent();

        return Inertia::render('Admin/Inventory/Index', [
            'products' => $products,
            'pagination' => ListPaginationPayload::from($paginator),
            'lowStockProductsCount' => (int) $lowStockProductsCount,
            'categories' => $categories->map(fn (Category $c): array => [
                'category_id' => (int) $c->category_id,
                'name' => $c->name,
            ])->values()->all(),
            'subcategoriesByParent' => $subcategoriesByParent,
            'brands' => Brand::orderBy('name')->get(['id', 'name'])->map(fn (Brand $b): array => [
                'id' => (int) $b->id,
                'name' => $b->name,
            ])->values()->all(),
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['supplier_id', 'name'])
                ->map(fn (Supplier $s): array => ['supplier_id' => (int) $s->supplier_id, 'name' => $s->name])
                ->values()->all(),
            'exportQuery' => AdminInventoryExportQuery::queryStringFromRequest($request),
            'blobUploadUrl' => config('vercel.enabled') ? '/internal/blob-client-upload' : '',
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'parent_category_id' => (string) $request->input('parent_category_id', ''),
                'subcategory_id' => (string) $request->input('subcategory_id', ''),
                'stock_status' => (string) $request->input('stock_status', ''),
                'status' => (string) $request->input('status', ''),
            ],
        ]);
    }
}
