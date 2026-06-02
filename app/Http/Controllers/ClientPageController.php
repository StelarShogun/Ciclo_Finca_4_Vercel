<?php

namespace App\Http\Controllers;

use App\Actions\Client\Cart\AddCartItem;
use App\Actions\Client\Cart\BuildCartPagePayload;
use App\Actions\Client\Cart\CheckoutCart;
use App\Actions\Client\Cart\ClearCart;
use App\Actions\Client\Cart\RemoveCartItem;
use App\Actions\Client\Cart\UpdateCartItem;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\FavoriteProduct;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderReadyToPickupNotification;
use App\Services\Catalog\CatalogProductSearchTelemetry;
use App\Services\Client\Cart\CartManager;
use App\Services\InventoryMovementService;
use App\Support\AdminPerPage;
use App\Support\ClientCategoryIcons;
use App\Support\ClientInertia\ListPaginationPayload;
use App\Support\ClientInertia\ProductDetailPayloadBuilder;
use App\Support\ClientInertia\ProductDetailPayloadContext;
use App\Support\ClientStorefrontCache;
use App\Support\ProductImageUrls;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClientPageController extends Controller
{
    private const PRODUCT_NOVELTY_DAYS = 30;

    public function __construct(
        private readonly CartManager $cartManager,
    ) {}

    public function home(): Response
    {
        // Load featured products that are active, in stock, and recently created.
        $featuredProducts = Product::with([
            'category.parent',
            'media' => static function ($q): void {
                $q->where('collection_name', 'main_image');
            },
        ])
            ->activeInClientStore()
            ->where('is_featured', true)
            ->where('stock_current', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $categories = $this->cachedClientRootCategories();

        $productReviewStats = ProductReview::aggregatesForProductIds(
            $featuredProducts->pluck('product_id')->map(fn ($id) => (int) $id)->all()
        );

        $showGuestRegisterCta = ! Auth::guard('clients')->check() && ! session('admin_catalog_mode');

        return Inertia::render('Client/Home/Index', [
            'featuredProducts' => $featuredProducts
                ->map(fn (Product $product): array => $this->homeProductPayload($product, $productReviewStats))
                ->values(),
            'categories' => $categories
                ->map(fn (Category $category): array => $this->homeCategoryPayload($category))
                ->values(),
            'showGuestRegisterCta' => $showGuestRegisterCta,
            'hero' => [
                'title' => 'Equípate para rodar',
                'emphasis' => 'con asesoría real en tienda',
                'subtitle' => 'Bicicletas, componentes y accesorios listos para encargo con retiro rápido.',
                'description' => 'Te guiamos en elección, ajuste y preparación para que retires con confianza.',
            ],
        ]);
    }

    /**
     * @param  array<int, array{avg: float|int|string|null, count: int|string|null}>  $productReviewStats
     * @return array<string, mixed>
     */
    private function homeProductPayload(Product $product, array $productReviewStats): array
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
    private function homeCategoryPayload(Category $category): array
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

    public function catalog(Request $request)
    {
        // Base del catálogo cliente: solo productos visibles/publicables para el cliente.
        $query = Product::with([
            'category.parent',
            'brands',
            'media' => static function ($q): void {
                $q->where('collection_name', 'main_image');
            },
        ])->activeInClientStore();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            });
        }

        // Filter by brand when brand_id is present in the request.
        $selectedBrand = null;
        if ($request->filled('brand_id')) {
            $brandId = (int) $request->brand_id;
            $selectedBrand = Brand::find($brandId);
            if ($selectedBrand) {
                $query->whereHas('brands', fn ($q) => $q->where('brands.id', $brandId));
            } else {
                // Brand does not exist — force empty result set (CA-03).
                $query->whereRaw('1 = 0');
            }
        }

        $selectedCategory = null;
        $subcategories = collect();
        $parentCategoryForSubcats = null;

        if ($request->filled('category_id')) {
            $selectedCategory = Category::find((int) $request->category_id);
            if ($selectedCategory) {
                // Include child categories when a parent category is selected.
                if (is_null($selectedCategory->parent_category_id)) {
                    $childIds = Category::where('parent_category_id', $selectedCategory->category_id)->pluck('category_id');
                    $query->where(function ($q) use ($selectedCategory, $childIds) {
                        $q->where('category_id', $selectedCategory->category_id)
                            ->orWhereIn('category_id', $childIds);
                    });
                    $subcategories = Category::where('parent_category_id', $selectedCategory->category_id)->orderBy('name')->get();
                    $parentCategoryForSubcats = $selectedCategory;
                } else {
                    $query->where('category_id', $selectedCategory->category_id);
                    $parentCategoryForSubcats = $selectedCategory->parent()->first();
                    if ($parentCategoryForSubcats instanceof Category) {
                        $subcategories = Category::where('parent_category_id', $parentCategoryForSubcats->category_id)->orderBy('name')->get();
                    }
                }
            }
        }

        $minPrice = $request->filled('min_price') ? $request->input('min_price') : null;
        $maxPrice = $request->filled('max_price') ? $request->input('max_price') : null;

        $minNegative = is_numeric($minPrice) && (float) $minPrice < 0;
        $maxNegative = is_numeric($maxPrice) && (float) $maxPrice < 0;
        if ($minNegative || $maxNegative) {
            return redirect()->route('clients.catalog', $request->except(['min_price', 'max_price', 'page']))
                ->withInput()
                ->withErrors(['price_range' => 'Los precios del filtro no pueden ser negativos.']);
        }

        // Reject invalid price ranges before applying filters.
        if (is_numeric($minPrice) && is_numeric($maxPrice) && (float) $minPrice > (float) $maxPrice) {
            return redirect()->route('clients.catalog', $request->except(['min_price', 'max_price', 'page']))
                ->withInput()
                ->withErrors(['price_range' => 'El precio mínimo debe ser menor o igual al precio máximo.']);
        }

        if (is_numeric($minPrice) && is_numeric($maxPrice)) {
            $query->whereBetween('sale_price', [$minPrice, $maxPrice]);
        } elseif (is_numeric($minPrice)) {
            $query->where('sale_price', '>=', $minPrice);
        } elseif (is_numeric($maxPrice)) {
            $query->where('sale_price', '<=', $maxPrice);
        }

        $sort = $request->get('sort', 'created_at');
        $order = $request->get('direction', 'desc');

        match ($sort) {
            'price' => $query->orderBy('sale_price', $order),
            'name' => $query->orderBy('name', $order),
            default => $query->orderBy('created_at', $order),
        };

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $products = $query->paginate($perPage)->withQueryString();

        if ($request->filled('search')) {
            CatalogProductSearchTelemetry::recordSearchResultsPage((string) $request->input('search'), $products);
        }

        $categories = $this->cachedClientRootCategories();

        $brands = $this->cachedClientBrandsForCatalog();

        $catalogSpotlight = $this->cachedCatalogSpotlightProductRows();
        $favoriteProductIds = collect();

        if (Auth::guard('clients')->check()) {
            $favoriteProductIds = FavoriteProduct::query()
                ->where('user_id', (int) Auth::guard('clients')->id())
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id);
        }

        $catalogParams = $request->except(['category_id', 'page']);
        $catalogCategoryNav = $this->buildCatalogCategoryNav($categories, $catalogParams);
        $emptyCategoryNoProducts = $request->filled('category_id')
            && $selectedCategory
            && $products->total() === 0;

        $catalogProductIdsForReviews = $products->getCollection()
            ->pluck('product_id')
            ->merge($catalogSpotlight->map(fn (array $row) => (int) $row['product']->product_id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $productReviewStats = ProductReview::aggregatesForProductIds($catalogProductIdsForReviews);

        $catalogVersion = ClientStorefrontCache::catalogVersion();

        return $this->clientCatalogResponse('Client/Catalog/Index', [
            'products' => $products->getCollection()
                ->map(fn (Product $product): array => $this->catalogProductPayload($product, $productReviewStats, $favoriteProductIds))
                ->values(),
            'pagination' => $this->catalogPaginationPayload($products),
            'categories' => $catalogCategoryNav,
            'brands' => $brands
                ->map(fn (Brand $brand): array => [
                    'id' => (int) $brand->id,
                    'name' => (string) $brand->name,
                ])
                ->values(),
            'filters' => $this->catalogFiltersPayload($request, $selectedCategory, $selectedBrand),
            'selectedBrand' => $selectedBrand ? [
                'id' => (int) $selectedBrand->id,
                'name' => (string) $selectedBrand->name,
            ] : null,
            'selectedCategory' => $selectedCategory ? $this->catalogCategorySummaryPayload($selectedCategory) : null,
            'subcategories' => $subcategories
                ->map(fn (Category $category): array => $this->catalogCategorySummaryPayload($category))
                ->values(),
            'parentCategoryForSubcats' => $parentCategoryForSubcats ? $this->catalogCategorySummaryPayload($parentCategoryForSubcats) : null,
            'catalogSpotlight' => $catalogSpotlight
                ->map(fn (array $row): array => [
                    'kind' => (string) $row['spotlight'],
                    'product' => $this->catalogProductPayload($row['product'], $productReviewStats, $favoriteProductIds),
                ])
                ->values(),
            'favoriteProductIds' => $favoriteProductIds->values(),
            'emptyCategoryNoProducts' => $emptyCategoryNoProducts,
            'catalogVersion' => $catalogVersion,
            'summary' => [
                'totalProducts' => (int) $products->total(),
                'totalCategories' => (int) $categories->count(),
                'activeFilterCount' => $this->catalogActiveFilterCount($request),
            ],
        ]);
    }

    public function catalogHeartbeat()
    {
        return response()
            ->json([
                'version' => ClientStorefrontCache::catalogVersion(),
            ])
            ->header('Cache-Control', 'private, no-cache, max-age=0, must-revalidate');
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function clientCatalogResponse(string $component, array $props)
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
    private function catalogProductPayload(Product $product, array $productReviewStats, Collection $favoriteProductIds): array
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
    private function catalogPaginationPayload(LengthAwarePaginator $products): array
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
    private function catalogFiltersPayload(Request $request, ?Category $selectedCategory, ?Brand $selectedBrand): array
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
    private function catalogCategorySummaryPayload(Category $category): array
    {
        return [
            'id' => (int) $category->category_id,
            'name' => (string) $category->name,
            'url' => route('clients.catalog', ['category_id' => $category->category_id]),
        ];
    }

    private function catalogActiveFilterCount(Request $request): int
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
    private function buildCatalogCategoryNav(Collection $rootCategories, array $catalogParams): array
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
    private function clientCatalogCategoryIconClass(?string $name): string
    {
        return ClientCategoryIcons::iconClassForName($name);
    }

    /** Árbol de categorías raíz + hijos (compartido entre inicio y catálogo). */
    private function cachedClientRootCategories(): Collection
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
    private function cachedClientBrandsForCatalog(): Collection
    {
        $ttl = ClientStorefrontCache::ttlSeconds((int) config('cf4_performance.client_brands_catalog_ttl', 60));

        return Cache::remember(ClientStorefrontCache::KEY_CATALOG_BRANDS, $ttl, function () {
            return Brand::query()
                ->orderBy('name')
                ->get();
        });
    }

    /** Spotlight del catálogo (destacados + novedades). */
    private function cachedCatalogSpotlightProductRows(): Collection
    {
        $ttl = ClientStorefrontCache::ttlSeconds((int) config('cf4_performance.client_catalog_spotlight_ttl', 60));

        return Cache::remember(ClientStorefrontCache::KEY_CATALOG_SPOTLIGHT, $ttl, function () {
            return $this->catalogSpotlightProductRowsUncached();
        });
    }

    // Returns spotlight rows using featured products first, then recent products.
    private function catalogSpotlightProductRowsUncached(): Collection
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

    public function product(Request $request, int $id, ?string $slug = null)
    {
        $product = Product::with(['category.parent', 'brands', 'classificationValues.dimension'])->findOrFail($id);

        // Redirect to the canonical product URL when the slug does not match.
        $canonicalSlug = $product->clientPublicSlug();
        if ($slug !== $canonicalSlug) {
            return redirect()->route('clients.product', array_merge(
                ['id' => $product->product_id, 'slug' => $canonicalSlug],
                $request->only(['reviews_sort', 'page', 'review_filter'])
            ), 301);
        }

        $relatedProducts = Product::with(['category.parent', 'brands'])
            ->where('category_id', $product->category_id)
            ->where('product_id', '!=', $product->product_id)
            ->limit(4)
            ->get();

        $favoriteProductIds = collect();
        if (Auth::guard('clients')->check()) {
            $favoriteProductIds = FavoriteProduct::query()
                ->where('user_id', (int) Auth::guard('clients')->id())
                ->pluck('product_id')
                ->map(fn ($pid) => (int) $pid);
        }

        $isProductFavorite = $favoriteProductIds->contains((int) $product->product_id);

        $taxonomy = $this->productDetailTaxonomy($product);
        $primaryBrand = $product->brands->first();
        $catalogBrandUrl = $primaryBrand
            ? route('clients.catalog', ['brand_id' => $primaryBrand->id])
            : null;

        $whatsappConsultUrl = $this->productWhatsappConsultUrl($product);
        $orderReservationHours = max(1, (int) config('sales.ready_to_pickup_expiration_hours', 72));

        $isNoveltyProduct = $product->created_at !== null
            && $product->created_at->greaterThanOrEqualTo(now()->subDays(self::PRODUCT_NOVELTY_DAYS));

        $cartCount = $this->cartManager->totalItemCount();

        $clientCanReview = false;
        $clientReview = null;
        $myHighlightedReview = null;
        if (Auth::guard('clients')->check()) {
            $clientId = (int) Auth::guard('clients')->id();
            $clientCanReview = SaleItem::query()
                ->where('product_id', $product->product_id)
                ->whereHas('sale', function ($q) use ($clientId) {
                    $q->where('client_id', $clientId)
                        ->where('status', 'completed');
                })
                ->exists();

            $clientReview = ProductReview::query()
                ->where('product_id', $product->product_id)
                ->where('client_id', $clientId)
                ->first();

            $myHighlightedReview = ProductReview::query()
                ->with(['client:user_id,name,first_surname,second_surname'])
                ->where('product_id', $product->product_id)
                ->where('client_id', $clientId)
                ->publiclyListed()
                ->first();
        }

        $aggregate = ProductReview::query()
            ->where('product_id', $product->product_id)
            ->publiclyListed()
            ->selectRaw('AVG(stars) as avg_stars, COUNT(*) as review_count')
            ->first();

        $totalReviewsCount = (int) ($aggregate->review_count ?? 0);
        $averageStars = $totalReviewsCount > 0
            ? round((float) $aggregate->avg_stars, 2)
            : null;

        $distributionCounts = ProductReview::query()
            ->where('product_id', $product->product_id)
            ->publiclyListed()
            ->selectRaw('stars, COUNT(*) as c')
            ->groupBy('stars')
            ->pluck('c', 'stars');

        $starDistribution = [];
        for ($s = 1; $s <= 5; $s++) {
            $starDistribution[$s] = (int) ($distributionCounts[$s] ?? 0);
        }

        $verifiedPurchaserIds = Sale::query()
            ->completed()
            ->whereHas('saleItems', function ($q) use ($product) {
                $q->where('product_id', $product->product_id);
            })
            ->distinct()
            ->pluck('client_id')
            ->map(fn ($id) => (int) $id);

        $reviewsSort = $request->query('reviews_sort', 'recent');
        if (! in_array($reviewsSort, ['recent', 'stars_high', 'stars_low'], true)) {
            $reviewsSort = 'recent';
        }

        $reviewFilter = $request->query('review_filter', 'all');
        if ($reviewFilter !== 'all' && (! ctype_digit((string) $reviewFilter) || ! in_array((int) $reviewFilter, [1, 2, 3, 4, 5], true))) {
            $reviewFilter = 'all';
        }

        $showMyHighlightedReview = $myHighlightedReview !== null
            && ($reviewFilter === 'all' || (int) $myHighlightedReview->stars === (int) $reviewFilter);

        $othersQuery = ProductReview::query()
            ->with(['client:user_id,name,first_surname,second_surname'])
            ->where('product_id', $product->product_id)
            ->publiclyListed();

        if ($myHighlightedReview !== null) {
            $othersQuery->where('review_id', '!=', $myHighlightedReview->review_id);
        }

        if ($reviewFilter !== 'all') {
            $othersQuery->where('stars', (int) $reviewFilter);
        }

        match ($reviewsSort) {
            'stars_high' => $othersQuery->orderByDesc('stars')->orderByDesc('created_at'),
            'stars_low' => $othersQuery->orderBy('stars')->orderByDesc('created_at'),
            default => $othersQuery->orderByDesc('created_at'),
        };

        $productReviewsPaginated = $othersQuery
            ->paginate(10)
            ->withQueryString();

        $productReviewStats = ProductReview::aggregatesForProductIds(
            array_values(array_unique(array_merge(
                [(int) $product->product_id],
                $relatedProducts->pluck('product_id')->map(fn ($id) => (int) $id)->all()
            )))
        );

        $product->load(['classificationValues.dimension']);

        $productDetailContext = new ProductDetailPayloadContext(
            product: $product,
            relatedProducts: $relatedProducts,
            favoriteProductIds: $favoriteProductIds,
            taxonomy: $taxonomy,
            primaryBrand: $primaryBrand,
            catalogBrandUrl: $catalogBrandUrl,
            isNoveltyProduct: $isNoveltyProduct,
            whatsappConsultUrl: $whatsappConsultUrl,
            orderReservationHours: $orderReservationHours,
            clientCanReview: $clientCanReview,
            clientReview: $clientReview,
            myHighlightedReview: $myHighlightedReview,
            showMyHighlightedReview: $showMyHighlightedReview,
            productReviewsPaginated: $productReviewsPaginated,
            totalReviewsCount: $totalReviewsCount,
            averageStars: $averageStars,
            starDistribution: $starDistribution,
            verifiedPurchaserIds: $verifiedPurchaserIds,
            reviewsSort: $reviewsSort,
            reviewFilter: $reviewFilter,
            productReviewStats: $productReviewStats,
            isProductFavorite: $isProductFavorite,
        );

        return Inertia::render(
            'Client/Products/Show',
            app(ProductDetailPayloadBuilder::class)->build($productDetailContext),
        );
    }

    private function productWhatsappConsultUrl(Product $product): ?string
    {
        $configured = config('cf4_legal.whatsapp_url');
        if (is_string($configured) && trim($configured) !== '') {
            return trim($configured);
        }

        $phone = config('cf4_legal.contact_phone');
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        $message = 'Hola, me gustaría consultar por: '.$product->name;

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    /**
     * @return array{parentCategory: ?Category, subcategory: ?Category, catalogParentUrl: ?string, catalogSubcategoryUrl: ?string}
     */
    private function productDetailTaxonomy(Product $product): array
    {
        $category = $product->category;
        if ($category === null) {
            return [
                'parentCategory' => null,
                'subcategory' => null,
                'catalogParentUrl' => null,
                'catalogSubcategoryUrl' => null,
            ];
        }

        if ($category->parent_category_id !== null) {
            $parentCategory = $category->parent;
            $subcategory = $category;

            return [
                'parentCategory' => $parentCategory,
                'subcategory' => $subcategory,
                'catalogParentUrl' => $parentCategory
                    ? route('clients.catalog', ['category_id' => $parentCategory->category_id])
                    : null,
                'catalogSubcategoryUrl' => route('clients.catalog', ['category_id' => $subcategory->category_id]),
            ];
        }

        return [
            'parentCategory' => $category,
            'subcategory' => null,
            'catalogParentUrl' => route('clients.catalog', ['category_id' => $category->category_id]),
            'catalogSubcategoryUrl' => null,
        ];
    }

    public function addToCart(Request $request, AddCartItem $action)
    {
        return $action->handle($request)->toJsonResponse();
    }

    public function updateCart(Request $request, UpdateCartItem $action)
    {
        return $action->handle($request)->toJsonResponse();
    }

    /**
     * Display cart. Session `cart` is the source of truth (minimal rows only).
     */
    public function cart(Request $request, BuildCartPagePayload $buildCartPagePayload)
    {
        return Inertia::render('Client/Cart/Index', $buildCartPagePayload->handle($request));
    }

    public function removeFromCart(int $id, RemoveCartItem $action)
    {
        return $action->handle($id)->toJsonResponse();
    }

    public function clearCart(ClearCart $action)
    {
        return $action->handle()->toJsonResponse();
    }

    public function checkout(Request $request, CheckoutCart $action, InventoryMovementService $inventoryService)
    {
        return $action->handle($request, $inventoryService)->toJsonResponse();
    }

    public function invoices(Request $request)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $tab = $request->query('tab', 'facturas');
        $pendingReviewProducts = collect();

        $activeStatuses = $this->activeClientInvoiceStatuses();
        $cancelledStatuses = $this->cancelledClientInvoiceStatuses();

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));

        if ($tab === 'historial') {
            $orders = Sale::query()
                ->where('client_id', $client->user_id)
                ->where('status', 'completed')
                ->orderByDesc('sale_date')
                ->paginate($perPage)
                ->withQueryString();

            Sale::markClientHistorySeen((int) $client->user_id);

            $pendingReviewProducts = ProductReview::query()
                ->with('product:product_id,name')
                ->where('client_id', $client->user_id)
                ->whereNull('stars')
                ->whereHas('product')
                ->get()
                ->map(function (ProductReview $review) {
                    return [
                        'product_id' => (int) $review->product_id,
                        'name' => (string) ($review->product->name ?? 'Producto'),
                    ];
                })
                ->values();
        } elseif ($tab === 'canceladas') {
            $orders = Sale::query()
                ->where('client_id', $client->user_id)
                ->whereIn('status', $cancelledStatuses)
                ->orderByDesc('sale_date')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $tab = 'facturas';

            $orders = Sale::query()
                ->where('client_id', $client->user_id)
                ->whereIn('status', $activeStatuses)
                ->orderByDesc('sale_date')
                ->paginate($perPage)
                ->withQueryString();
        }

        $cartCount = $this->cartManager->totalItemCount();

        $invoiceCount = Sale::countActiveClientInvoices((int) $client->user_id);
        $unseenHistoryCount = Sale::countUnseenInClientHistory((int) $client->user_id);
        $invoicesRevision = Sale::clientInvoicesRevision((int) $client->user_id);
        $readyToPickupCount = Sale::query()
            ->where('client_id', $client->user_id)
            ->where('status', 'ready_to_pickup')
            ->count();

        $ordersRows = collect($orders->items())->map(function (Sale $sale) {
            $statusLabel = match ($sale->status) {
                'pending' => 'Pendiente',
                'ready_to_pickup' => 'Por recoger',
                'cancelled', 'canceled' => 'Cancelada',
                'completed' => 'Confirmado',
                default => ucfirst(str_replace('_', ' ', (string) $sale->status)),
            };

            $statusTone = match ($sale->status) {
                'pending' => 'pending',
                'ready_to_pickup' => 'ready',
                'cancelled', 'canceled' => 'cancelled',
                'completed' => 'completed',
                default => 'default',
            };

            return [
                'id' => (int) $sale->sale_id,
                'invoiceNumber' => $sale->invoice_number ? (string) $sale->invoice_number : null,
                'saleDateLabel' => $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : 'Sin fecha',
                'statusLabel' => $statusLabel,
                'statusTone' => $statusTone,
                'totalFormatted' => '₡'.number_format((float) $sale->total, 0, ',', '.'),
                'showUrl' => route('clients.invoices.show', $sale, false),
            ];
        })->values()->all();

        return Inertia::render('Client/Invoices/Index', [
            'tab' => $tab,
            'orders' => $ordersRows,
            'pagination' => ListPaginationPayload::from($orders),
            'cartCount' => $cartCount,
            'invoiceCount' => $invoiceCount,
            'unseenHistoryCount' => $unseenHistoryCount,
            'invoicesRevision' => $invoicesRevision,
            'readyToPickupCount' => (int) $readyToPickupCount,
            'heartbeatUrl' => route('clients.invoices.heartbeat', [], false),
            'pendingReviewProducts' => $pendingReviewProducts,
        ]);
    }

    public function invoicesHeartbeat()
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $clientId = (int) $client->user_id;

        return response()->json([
            'count' => Sale::countActiveClientInvoices($clientId),
            'unseen_history' => Sale::countUnseenInClientHistory($clientId),
            'revision' => Sale::clientInvoicesRevision($clientId),
        ]);
    }

    public function notificationsHeartbeat()
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $clientId = (int) $client->user_id;

        $typeMap = [
            OrderReadyToPickupNotification::class => [
                'kind' => 'ready_to_pickup',
                'title' => '¡Listo para recoger!',
            ],
            OrderCompletedNotification::class => [
                'kind' => 'completed',
                'title' => '¡Pedido confirmado!',
            ],
            OrderCancelledNotification::class => [
                'kind' => 'cancelled',
                'title' => 'Pedido cancelado',
            ],
        ];

        $toasts = $client->unreadNotifications()
            ->whereIn('type', array_keys($typeMap))
            ->latest()
            ->limit(5)
            ->get()
            ->map(static function ($notification) use ($typeMap) {
                $data = is_array($notification->data) ? $notification->data : [];
                $meta = $typeMap[$notification->type] ?? ['kind' => 'info', 'title' => 'Notificación'];

                return [
                    'id' => (string) $notification->id,
                    'kind' => $meta['kind'],
                    'title' => $meta['title'],
                    'message' => (string) ($data['message'] ?? ''),
                    'action_url' => (string) ($data['action_url'] ?? route('clients.invoices', [], false)),
                    'action_label' => (string) ($data['action_label'] ?? 'Ver facturas'),
                ];
            })
            ->values();

        return response()->json([
            'unread_count' => $client->unreadNotifications()->count(),
            'invoice_count' => Sale::countActiveClientInvoices($clientId),
            'unseen_history' => Sale::countUnseenInClientHistory($clientId),
            'revision' => Sale::clientInvoicesRevision($clientId),
            'toasts' => $toasts,
        ]);
    }

    public function notifications(Request $request)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $client->unreadNotifications->markAsRead();

        $cartCount = $this->cartManager->totalItemCount();

        $notifications = $client->notifications()
            ->latest()
            ->paginate(AdminPerPage::resolve($request->input('per_page', 10)))
            ->withQueryString();

        $rows = collect($notifications->items())->map(function ($notification) {
            $data = is_array($notification->data) ? $notification->data : [];

            return [
                'id' => (string) $notification->id,
                'createdAtLabel' => optional($notification->created_at)->format('d/m/Y H:i') ?? '',
                'message' => (string) ($data['message'] ?? 'Notificación del sistema'),
                'actionUrl' => $data['action_url'] ?? null,
                'actionLabel' => $data['action_label'] ?? 'Abrir enlace',
            ];
        })->values()->all();

        return Inertia::render('Client/Notifications/Index', [
            'notifications' => $rows,
            'pagination' => ListPaginationPayload::from($notifications),
            'cartCount' => $cartCount,
        ]);
    }

    public function showInvoice(Sale $sale)
    {
        $client = Auth::guard('clients')->user();

        if ((int) $sale->client_id !== (int) $client->user_id) {
            abort(404);
        }

        $sale->load(['saleItems.product', 'client', 'sellerAdmin']);

        $cartCount = $this->cartManager->totalItemCount();

        $invoiceCount = Sale::countActiveClientInvoices((int) $client->user_id);

        $documentKind = $sale->clientInvoiceDocumentKind();
        $documentTitle = $documentKind === 'invoice' ? 'Factura' : 'Comprobante';
        $items = $sale->saleItems ?? collect();
        $itemsCount = $items->sum(fn ($item) => (int) $item->quantity);

        $subtotalCalc = $items->sum(function ($item) {
            return $item->total !== null
                ? (float) $item->total
                : ((float) $item->unit_price * (int) $item->quantity);
        });

        $subtotalDisplay = $sale->subtotal !== null ? (float) $sale->subtotal : $subtotalCalc;
        $ivaDisplay = (float) ($sale->iva ?? 0);
        $discountDisplay = (float) ($sale->discount ?? 0);
        $totalDisplay = $sale->total !== null
            ? (float) $sale->total
            : ($subtotalDisplay + $ivaDisplay - $discountDisplay);

        $paymentLabels = [
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'sinpe' => 'SINPE Móvil',
        ];
        $paymentDisplay = $sale->payment_method
            ? ($paymentLabels[strtolower((string) $sale->payment_method)] ?? ucfirst((string) $sale->payment_method))
            : 'No registrado';

        $sourceLabels = [
            'web_cart' => 'Tienda web',
            'pos' => 'Punto de venta',
            'in_store' => 'Tienda física',
        ];
        $sourceDisplay = $sale->order_source
            ? ($sourceLabels[strtolower((string) $sale->order_source)] ?? ucfirst((string) $sale->order_source))
            : 'Tienda web';

        $backUrl = route('clients.invoices', ['tab' => $sale->clientInvoicesBackTab()], false);

        return Inertia::render('Client/Invoices/Show', [
            'invoiceCount' => (int) $invoiceCount,
            'backUrl' => $backUrl,
            'cartCount' => $cartCount,
            'documentTitle' => $documentTitle,
            'invoiceNumber' => $sale->invoice_number ? (string) $sale->invoice_number : null,
            'orderMeta' => [
                'saleId' => (int) $sale->sale_id,
                'saleDateLabel' => $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : 'Sin fecha',
                'statusLabel' => $sale->clientStatusLabel(),
                'statusPillClass' => $sale->clientStatusPillClass(),
                'statusIconClass' => $sale->clientStatusIconClass(),
                'cancellationReason' => $sale->clientCancellationReason(),
                'paymentDisplay' => $paymentDisplay,
                'sourceDisplay' => $sourceDisplay,
            ],
            'totals' => [
                'subtotalFormatted' => '₡'.number_format($subtotalDisplay, 0, ',', '.'),
                'ivaFormatted' => '₡'.number_format($ivaDisplay, 0, ',', '.'),
                'discountFormatted' => '₡'.number_format($discountDisplay, 0, ',', '.'),
                'totalFormatted' => '₡'.number_format($totalDisplay, 0, ',', '.'),
                'itemsCount' => (int) $itemsCount,
            ],
            'items' => collect($items)->map(function (SaleItem $item) {
                $total = $item->total !== null
                    ? (float) $item->total
                    : ((float) $item->unit_price * (int) $item->quantity);

                return [
                    'productId' => (int) $item->product_id,
                    'name' => (string) ($item->product->name ?? 'Producto'),
                    'quantity' => (int) $item->quantity,
                    'unitPriceFormatted' => '₡'.number_format((float) $item->unit_price, 0, ',', '.'),
                    'totalFormatted' => '₡'.number_format($total, 0, ',', '.'),
                ];
            })->values()->all(),
            'printUrl' => route('clients.invoices.print', $sale, false),
        ]);
    }

    public function printInvoice(Sale $sale)
    {
        $client = Auth::guard('clients')->user();

        if ((int) $sale->client_id !== (int) $client->user_id) {
            abort(404);
        }

        $sale->load(['saleItems.product', 'client', 'sellerAdmin']);

        return view('client.invoice-print', compact('sale'));
    }

    private function activeClientInvoiceStatuses(): array
    {
        return Sale::activeClientInvoiceStatuses();
    }

    private function cancelledClientInvoiceStatuses(): array
    {
        return ['cancelled', 'canceled'];
    }
}
