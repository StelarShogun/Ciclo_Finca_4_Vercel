<?php

namespace App\Services\Admin\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Admin\AdminInventoryExportQuery;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Services\Shared\Media\ProductImageUrls;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;

final readonly class ProductPayloadBuilder
{
    public function __construct(
        private AdminInventoryProductQuery $inventoryProductQuery,
        private InventoryClassificationFilterService $classificationFilters,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function inventoryIndex(Request $request): array
    {
        $query = $this->inventoryProductQuery->filteredQuery($request)->with(['category.parent', 'supplier']);
        $lowStockProductsCount = Product::query()->lowStockAlert()->count();
        $perPage = AdminPerPage::resolve($request->get('per_page', 10));
        $paginator = $query->paginate($perPage)->withQueryString();

        return [
            'products' => collect($paginator->items())
                ->filter(fn ($product) => $product instanceof Product)
                ->map(fn (Product $product): array => $this->productRow($product))
                ->values()
                ->all(),
            'pagination' => ListPaginationPayload::from($paginator),
            'lowStockProductsCount' => (int) $lowStockProductsCount,
            'inventorySummary' => [
                'total' => (int) Product::query()->count(),
                'active' => (int) Product::query()->where('status', 'active')->count(),
                'lowStock' => (int) $lowStockProductsCount,
                'outOfStock' => (int) Product::query()->where('stock_current', '<=', 0)->count(),
            ],
            'categories' => $this->categories(),
            'subcategoriesByParent' => Category::subcategoriesGroupedByCanonicalParent(),
            'brands' => $this->brands(),
            'suppliers' => $this->suppliers(),
            'exportQuery' => AdminInventoryExportQuery::queryStringFromRequest($request),
            'blobUploadUrl' => config('vercel.enabled') ? '/internal/blob-client-upload' : '',
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'parent_category_id' => (string) $request->input('parent_category_id', ''),
                'subcategory_id' => (string) $request->input('subcategory_id', ''),
                'stock_status' => (string) $request->input('stock_status', ''),
                'status' => (string) $request->input('status', ''),
            ],
            'activeClassificationFilters' => $this->activeClassificationFilters($request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productRow(Product $product): array
    {
        return [
            'product_id' => (int) $product->product_id,
            'name' => $product->name,
            'sku' => $product->displaySku(),
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
            'status_label' => [
                'active' => 'Activo',
                'inactive' => 'Inactivo',
                'out_of_stock' => 'Agotado',
                'discontinued' => 'Descontinuado',
            ][$product->status] ?? ucfirst(str_replace('_', ' ', (string) $product->status)),
            'status_class' => [
                'active' => 'success',
                'inactive' => 'warning',
                'out_of_stock' => 'danger',
                'discontinued' => 'secondary',
            ][$product->status] ?? 'secondary',
            'is_featured' => (bool) $product->is_featured,
        ];
    }

    /**
     * @return list<array{category_id: int, name: string}>
     */
    private function categories(): array
    {
        return Category::query()
            ->selectRaw('MIN(category_id) as category_id, name')
            ->whereNull('parent_category_id')
            ->groupBy('name')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'category_id' => (int) $category->category_id,
                'name' => $category->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function brands(): array
    {
        return Brand::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Brand $brand): array => [
                'id' => (int) $brand->id,
                'name' => $brand->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{supplier_id: int, name: string}>
     */
    private function suppliers(): array
    {
        return Supplier::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['supplier_id', 'name'])
            ->map(fn (Supplier $supplier): array => [
                'supplier_id' => (int) $supplier->supplier_id,
                'name' => $supplier->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int|string, mixed>
     */
    private function activeClassificationFilters(Request $request): array
    {
        $hasSelections = collect((array) $request->input('classifications', []))
            ->contains(fn ($value) => is_string($value) && trim($value) !== '');

        return $hasSelections ? $this->classificationFilters->activeFilters($request) : [];
    }
}
