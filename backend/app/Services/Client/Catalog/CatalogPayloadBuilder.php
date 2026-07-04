<?php

namespace App\Services\Client\Catalog;

use App\DTOs\Client\Catalog\CatalogFilterResolution;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Services\Shared\Media\ProductImageUrls;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class CatalogPayloadBuilder
{
    private const PRODUCT_NOVELTY_DAYS = 30;

    public function __construct(
        private CatalogCategoryNavigationBuilder $categoryNavigation,
        private CatalogFilterResolver $filterResolver,
        private CatalogSpotlightBuilder $spotlightBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        Request $request,
        CatalogFilterResolution $filters,
        LengthAwarePaginator $products,
        Collection $favoriteProductIds,
    ): array {
        $categories = $this->categoryNavigation->rootCategories();
        $brands = $this->cachedBrands();
        $catalogSpotlight = $this->spotlightBuilder->rows();

        $catalogParams = $request->except(['category_id', 'page']);
        $catalogCategoryNav = $this->categoryNavigation->navigation($categories, $catalogParams);

        $emptyCategoryNoProducts = $request->filled('category_id')
            && $filters->selectedCategory
            && $products->total() === 0;

        $catalogProductIdsForReviews = $products->getCollection()
            ->pluck('product_id')
            ->merge($catalogSpotlight->map(fn (array $row) => (int) $row['product']->product_id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $productReviewStats = ProductReview::aggregatesForProductIds($catalogProductIdsForReviews);

        return [
            'products' => $products->getCollection()
                ->map(fn (Product $product): array => $this->productPayload($product, $productReviewStats, $favoriteProductIds))
                ->values(),
            'pagination' => $this->paginationPayload($products),
            'categories' => $catalogCategoryNav,
            'brands' => $brands
                ->map(fn (Brand $brand): array => [
                    'id' => (int) $brand->id,
                    'name' => (string) $brand->name,
                ])
                ->values(),
            'filters' => $this->filtersPayload($request, $filters),
            'selectedBrand' => $filters->selectedBrand ? [
                'id' => (int) $filters->selectedBrand->id,
                'name' => (string) $filters->selectedBrand->name,
            ] : null,
            'selectedCategory' => $filters->selectedCategory
                ? $this->categoryNavigation->categorySummary($filters->selectedCategory)
                : null,
            'subcategories' => $filters->subcategories
                ->map(fn (Category $category): array => $this->categoryNavigation->categorySummary($category))
                ->values(),
            'parentCategoryForSubcats' => $filters->parentCategoryForSubcats
                ? $this->categoryNavigation->categorySummary($filters->parentCategoryForSubcats)
                : null,
            'catalogSpotlight' => $catalogSpotlight
                ->map(fn (array $row): array => [
                    'kind' => (string) $row['spotlight'],
                    'product' => $this->productPayload($row['product'], $productReviewStats, $favoriteProductIds),
                ])
                ->values(),
            'favoriteProductIds' => $favoriteProductIds->values(),
            'emptyCategoryNoProducts' => $emptyCategoryNoProducts,
            'catalogVersion' => ClientStorefrontCache::catalogVersion(),
            'summary' => [
                'totalProducts' => (int) $products->total(),
                'totalCategories' => (int) $categories->count(),
                'activeFilterCount' => $this->filterResolver->activeFilterCount($request),
            ],
        ];
    }

    /**
     * @param  array<int, array{avg: float|int|string|null, count: int|string|null}>  $productReviewStats
     * @param  Collection<int, int>  $favoriteProductIds
     * @return array<string, mixed>
     */
    private function productPayload(Product $product, array $productReviewStats, Collection $favoriteProductIds): array
    {
        $picture = ProductImageUrls::cardPicture($product);
        $reviewStats = $productReviewStats[(int) $product->product_id] ?? null;
        $category = $product->category;
        $parentCategory = $category?->parent;
        $stockLabel = $product->clientCatalogStockLabel();

        return [
            'id' => (int) $product->product_id,
            'name' => (string) $product->name,
            'description' => $product->description ? Str::limit((string) $product->description, 120) : null,
            'price' => (float) $product->sale_price,
            'priceFormatted' => '₡'.number_format((float) $product->sale_price, 0, ',', '.'),
            'stockCurrent' => (int) ($product->stock_current ?? 0),
            'stockLabel' => $stockLabel,
            'canBuy' => $product->isPurchasableByClient(),
            'isFeatured' => (bool) $product->is_featured,
            'isNew' => $product->created_at !== null && $product->created_at->greaterThanOrEqualTo(now()->subDays(self::PRODUCT_NOVELTY_DAYS)),
            'isFavorite' => $favoriteProductIds->contains((int) $product->product_id),
            'sku' => $product->clientCatalogAssignedSku(),
            'url' => $product->clientProductUrl(),
            'category' => $category ? [
                'id' => (int) $category->category_id,
                'name' => (string) $category->name,
            ] : null,
            'parentCategory' => $parentCategory ? [
                'id' => (int) $parentCategory->category_id,
                'name' => (string) $parentCategory->name,
            ] : null,
            'brands' => $product->brands
                ->map(fn (Brand $brand): array => [
                    'id' => (int) $brand->id,
                    'name' => (string) $brand->name,
                ])
                ->values(),
            'image' => [
                'fallback' => $picture['fallback'],
                'desktopWebp' => $picture['desktopWebp'],
                'mobileWebp' => $picture['mobileWebp'],
                'usesPlaceholder' => ProductImageUrls::usesPlaceholder($product),
                'placeholderIconClass' => ProductImageUrls::placeholderIconClass($product),
            ],
            'reviews' => [
                'avg' => (float) data_get($reviewStats, 'avg', 0),
                'count' => (int) data_get($reviewStats, 'count', 0),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paginationPayload(LengthAwarePaginator $products): array
    {
        return [
            'currentPage' => (int) $products->currentPage(),
            'lastPage' => (int) $products->lastPage(),
            'perPage' => (int) $products->perPage(),
            'total' => (int) $products->total(),
            'from' => $products->firstItem(),
            'to' => $products->lastItem(),
            'links' => collect($products->linkCollection())->map(fn (array $link): array => [
                'url' => $link['url'],
                'label' => (string) $link['label'],
                'active' => (bool) $link['active'],
                'page' => $link['page'] ?? null,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersPayload(Request $request, CatalogFilterResolution $filters): array
    {
        return [
            'search' => (string) $request->input('search', ''),
            'categoryId' => $filters->selectedCategory ? (int) $filters->selectedCategory->category_id : null,
            'brandId' => $filters->selectedBrand ? (int) $filters->selectedBrand->id : null,
            'minPrice' => $request->filled('min_price') ? (string) $request->input('min_price') : '',
            'maxPrice' => $request->filled('max_price') ? (string) $request->input('max_price') : '',
            'sort' => (string) $request->input('sort', 'created_at'),
            'direction' => (string) $request->input('direction', 'desc'),
            'perPage' => AdminPerPage::resolve($request->input('per_page', 10)),
        ];
    }

    /**
     * @return Collection<int, Brand>
     */
    private function cachedBrands(): Collection
    {
        $ttl = ClientStorefrontCache::ttlSeconds((int) config('cf4_performance.client_brands_catalog_ttl', 60));

        return Cache::remember(ClientStorefrontCache::KEY_CATALOG_BRANDS, $ttl, function () {
            return Brand::query()
                ->orderBy('name')
                ->get();
        });
    }
}
