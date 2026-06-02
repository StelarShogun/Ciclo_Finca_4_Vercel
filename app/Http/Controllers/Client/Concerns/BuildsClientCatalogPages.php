<?php

namespace App\Http\Controllers\Client\Concerns;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\AdminPerPage;
use App\Support\ClientCategoryIcons;
use App\Support\ClientStorefrontCache;
use App\Support\ProductImageUrls;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

trait BuildsClientCatalogPages
{
    protected const PRODUCT_NOVELTY_DAYS = 30;

    /**
     * @param  array<int, array{avg: float|int|string|null, count: int|string|null}>  $productReviewStats
     * @return array<string, mixed>
     */
    protected function homeProductPayload(Product $product, array $productReviewStats): array
    {
        $stockLabel = $product->clientCatalogStockLabel();
        $picture = ProductImageUrls::cardPicture($product);
        $reviewStats = $productReviewStats[(int) $product->product_id] ?? null;

        return [
            'id' => (int) $product->product_id,
            'name' => (string) $product->name,
            'description' => $product->description ? Str::limit((string) $product->description, 80) : null,
            'category' => $product->category?->name ?? 'Uncategorized',
            'price' => (float) $product->sale_price,
            'priceFormatted' => '₡'.number_format((float) $product->sale_price, 0, ',', '.'),
            'stockCurrent' => (int) ($product->stock_current ?? 0),
            'stockLabel' => $stockLabel,
            'canBuy' => $product->isPurchasableByClient(),
            'sku' => $product->clientCatalogAssignedSku(),
            'url' => $product->clientProductUrl(),
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
    protected function homeCategoryPayload(Category $category): array
    {
        return [
            'id' => (int) $category->category_id,
            'name' => (string) $category->name,
            'description' => $category->description ? Str::limit((string) $category->description, 72) : null,
            'url' => route('clients.catalog', ['category_id' => $category->category_id]),
            'iconClass' => ClientCategoryIcons::iconClassForName((string) $category->name),
            'children' => $category->childCategories
                ->map(fn (Category $child): array => [
                    'id' => (int) $child->category_id,
                    'name' => (string) $child->name,
                    'url' => route('clients.catalog', ['category_id' => $child->category_id]),
                ])
                ->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function clientCatalogResponse(string $component, array $props)
    {
        return Inertia::render($component, $props)
            ->toResponse(request())
            ->header('Cache-Control', 'private, no-cache, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    /**
     * @param  array<int, array{avg: float|int|string|null, count: int|string|null}>  $productReviewStats
     * @param  Collection<int, int>  $favoriteProductIds
     * @return array<string, mixed>
     */
    protected function catalogProductPayload(Product $product, array $productReviewStats, Collection $favoriteProductIds): array
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
    protected function catalogPaginationPayload(LengthAwarePaginator $products): array
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
    protected function catalogFiltersPayload(Request $request, ?Category $selectedCategory, ?Brand $selectedBrand): array
    {
        return [
            'search' => (string) $request->input('search', ''),
            'categoryId' => $selectedCategory ? (int) $selectedCategory->category_id : null,
            'brandId' => $selectedBrand ? (int) $selectedBrand->id : null,
            'minPrice' => $request->filled('min_price') ? (string) $request->input('min_price') : '',
            'maxPrice' => $request->filled('max_price') ? (string) $request->input('max_price') : '',
            'sort' => (string) $request->input('sort', 'created_at'),
            'direction' => (string) $request->input('direction', 'desc'),
            'perPage' => AdminPerPage::resolve($request->input('per_page', 10)),
        ];
    }

    /**
     * @return array{id: int, name: string, url: string}
     */
    protected function catalogCategorySummaryPayload(Category $category): array
    {
        return [
            'id' => (int) $category->category_id,
            'name' => (string) $category->name,
            'url' => route('clients.catalog', ['category_id' => $category->category_id]),
        ];
    }

    protected function catalogActiveFilterCount(Request $request): int
    {
        return collect(['min_price', 'max_price', 'brand_id', 'search'])
            ->filter(fn (string $key): bool => $request->filled($key))
            ->count();
    }

    /**
     * Árbol de categorías para el catálogo cliente (JSON + sidebar/panel). Sin columna extra en BD.
     *
     * @param  Collection<int, Category>  $rootCategories
     * @return array<int, array{id: int, name: string, icon: string, url_parent: string, children: array<int, array{id: int, name: string, url: string}>}>
     */
    /**
     * @param  Collection<int, Category>  $rootCategories
     * @return array<int, array{id: int, name: string, icon: string, url_parent: string, children: array<int, array{id: int, name: string, url: string}>}>
     */
    protected function buildCatalogCategoryNav(Collection $rootCategories, array $catalogParams): array
    {
        return $rootCategories->map(function (Category $c) use ($catalogParams) {
            return [
                'id' => (int) $c->category_id,
                'name' => $c->name,
                'icon' => $this->clientCatalogCategoryIconClass($c->name),
                'url_parent' => route('clients.catalog', array_merge($catalogParams, ['category_id' => $c->category_id])),
                'children' => $c->childCategories->map(function (Category $ch) use ($catalogParams) {
                    return [
                        'id' => (int) $ch->category_id,
                        'name' => $ch->name,
                        'url' => route('clients.catalog', array_merge($catalogParams, ['category_id' => $ch->category_id])),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    /** Clases Font Awesome (fas fa-*) por heurística de nombre — sin icono en BD. */
    protected function clientCatalogCategoryIconClass(?string $name): string
    {
        return ClientCategoryIcons::iconClassForName($name);
    }

    /** Árbol de categorías raíz + hijos (compartido entre inicio y catálogo). */
    protected function cachedClientRootCategories(): Collection
    {
        $ttl = ClientStorefrontCache::ttlSeconds((int) config('cf4_performance.client_root_categories_ttl', 60));

        return Cache::remember(ClientStorefrontCache::KEY_ROOT_CATEGORIES, $ttl, function () {
            return Category::whereNull('parent_category_id')
                ->with(['childCategories' => function ($q) {
                    $q->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
        });
    }

    /** Marcas disponibles en el filtro del catálogo (incluye marcas recién creadas sin productos). */
    protected function cachedClientBrandsForCatalog(): Collection
    {
        $ttl = ClientStorefrontCache::ttlSeconds((int) config('cf4_performance.client_brands_catalog_ttl', 60));

        return Cache::remember(ClientStorefrontCache::KEY_CATALOG_BRANDS, $ttl, function () {
            return Brand::query()
                ->orderBy('name')
                ->get();
        });
    }

    /** Spotlight del catálogo (destacados + novedades). */
    protected function cachedCatalogSpotlightProductRows(): Collection
    {
        $ttl = ClientStorefrontCache::ttlSeconds((int) config('cf4_performance.client_catalog_spotlight_ttl', 60));

        return Cache::remember(ClientStorefrontCache::KEY_CATALOG_SPOTLIGHT, $ttl, function () {
            return $this->catalogSpotlightProductRowsUncached();
        });
    }

    // Returns spotlight rows using featured products first, then recent products.
    protected function catalogSpotlightProductRowsUncached(): Collection
    {
        $maxTotal = 12;
        $maxFeatured = 8;

        $featured = Product::with([
            'category.parent',
            'media' => static function ($q): void {
                $q->where('collection_name', 'main_image');
            },
        ])
            ->activeInClientStore()
            ->where('is_featured', true)
            ->orderByDesc('created_at')
            ->limit($maxFeatured)
            ->get();

        $featuredIds = $featured->pluck('product_id')->all();
        $remaining = max(0, $maxTotal - $featured->count());

        $novelties = $remaining > 0
            ? Product::with([
                'category.parent',
                'media' => static function ($q): void {
                    $q->where('collection_name', 'main_image');
                },
            ])
                ->activeInClientStore()
                ->whereNotIn('product_id', $featuredIds)
                ->orderByDesc('created_at')
                ->limit($remaining)
                ->get()
            : collect();

        return $featured->map(fn (Product $p) => ['product' => $p, 'spotlight' => 'featured'])
            ->concat($novelties->map(fn (Product $p) => ['product' => $p, 'spotlight' => 'novelty']));
    }
}
