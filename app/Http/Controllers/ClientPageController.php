<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\FavoriteProduct;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\CartService;
use App\Services\Catalog\CatalogProductSearchTelemetry;
use App\Services\InventoryMovementService;
use App\Support\AdminPerPage;
use App\Support\ClientCategoryIcons;
use App\Support\ClientStorefrontCache;
use App\Support\ProductImageUrls;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class ClientPageController extends Controller
{
    private const PRODUCT_NOVELTY_DAYS = 30;

    public function home()
    {
        // Load featured products that are active, in stock, and recently created.
        $featuredProducts = Product::with(['category.parent'])
            ->activeInClientStore()
            ->where('is_featured', true)
            ->where('stock_current', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $categories = $this->cachedClientRootCategories();

        $cartCount = $this->getCartCount();

        $productReviewStats = ProductReview::aggregatesForProductIds(
            $featuredProducts->pluck('product_id')->map(fn ($id) => (int) $id)->all()
        );

        $showGuestRegisterCta = ! Auth::guard('clients')->check() && ! session('admin_catalog_mode');

        return view('client.home', compact(
            'featuredProducts',
            'categories',
            'cartCount',
            'productReviewStats',
            'showGuestRegisterCta',
        ));
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

        $cartCount = $this->getCartCount();
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

        return $this->clientCatalogResponse('client.catalog', compact(
            'products',
            'categories',
            'cartCount',
            'selectedCategory',
            'subcategories',
            'parentCategoryForSubcats',
            'catalogSpotlight',
            'favoriteProductIds',
            'catalogParams',
            'catalogCategoryNav',
            'emptyCategoryNoProducts',
            'brands',
            'selectedBrand',
            'productReviewStats',
            'catalogVersion',
        ));
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
     * @param  array<string, mixed>  $data
     */
    private function clientCatalogResponse(string $view, array $data)
    {
        return response()
            ->view($view, $data)
            ->header('Cache-Control', 'private, no-cache, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache');
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

        $cartCount = $this->getCartCount();

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

        return view('client.product', compact(
            'product',
            'relatedProducts',
            'cartCount',
            'clientCanReview',
            'clientReview',
            'myHighlightedReview',
            'showMyHighlightedReview',
            'productReviewsPaginated',
            'totalReviewsCount',
            'averageStars',
            'starDistribution',
            'verifiedPurchaserIds',
            'reviewsSort',
            'reviewFilter',
            'productReviewStats',
            'favoriteProductIds',
            'isProductFavorite',
            'taxonomy',
            'primaryBrand',
            'catalogBrandUrl',
            'isNoveltyProduct',
            'whatsappConsultUrl',
            'orderReservationHours',
        ));
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

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (! $product->isPurchasableByClient()) {
            return response()->json(['success' => false, 'message' => Product::MSG_CLIENT_AGOTADO], 400);
        }

        if ($product->stock_current < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => $product->stock_current < 1
                    ? Product::MSG_CLIENT_AGOTADO
                    : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
            ], 400);
        }

        $cart = Session::get('cart', []);
        $existingIndex = null;

        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $request->product_id) {
                $existingIndex = $index;
                break;
            }
        }

        // Increase quantity when the product already exists in the cart.
        if ($existingIndex !== null) {
            $newQuantity = ($cart[$existingIndex]['quantity'] ?? 0) + $request->quantity;

            if ($newQuantity > $product->stock_current) {
                return response()->json([
                    'success' => false,
                    'message' => $product->stock_current < 1
                        ? Product::MSG_CLIENT_AGOTADO
                        : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
                ], 400);
            }

            $cart[$existingIndex]['quantity'] = $newQuantity;
        } else {
            $mediaUrl = $product->getFirstMediaUrl('main_image');
            $cart[] = [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->sale_price,
                'quantity' => $request->quantity,
                'image' => $mediaUrl,
            ];
        }

        Session::put('cart', $cart);

        $authClient = Auth::guard('clients')->user();
        if ($authClient) {
            CartService::saveToDb($authClient->user_id, $cart);
        }

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    /**
     * Clamp quantities to current stock and drop unpurchasable lines. Updates session only when it changes.
     */
    private function syncCartWithStock(): void
    {
        $before = Session::get('cart', []);
        $synced = [];
        $adjustedNames = [];

        foreach ($before as $item) {
            if (! isset($item['product_id'])) {
                continue;
            }

            $product = Product::find($item['product_id']);

            if (! $product || ! $product->isPurchasableByClient()) {
                continue;
            }

            $requested = (int) ($item['quantity'] ?? 0);
            $qty = min($requested, (int) $product->stock_current);

            if ($qty < 1) {
                continue;
            }

            if ($qty < $requested) {
                $adjustedNames[] = $product->name;
            }

            $synced[] = [
                'product_id' => (int) $product->product_id,
                'name' => (string) ($item['name'] ?? $product->name),
                'price' => (float) ($item['price'] ?? $product->sale_price),
                'quantity' => $qty,
                'image' => (string) ($item['image'] ?? ''),
            ];
        }

        $needsPut = ! $this->cartsAreEquivalent($before, $synced)
            || $this->sessionCartHasNonMinimalKeys($before);

        if ($needsPut) {
            Session::put('cart', $synced);
            $authClient = Auth::guard('clients')->user();
            if ($authClient) {
                CartService::saveToDb($authClient->user_id, $synced);
            }
        }

        if ($adjustedNames !== []) {
            session()->flash(
                'cart_stock_adjusted',
                'Ajustamos el carrito al stock disponible para: '.implode(', ', array_unique($adjustedNames)).'.'
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $a
     * @param  array<int, array<string, mixed>>  $b
     */
    private function cartsAreEquivalent(array $a, array $b): bool
    {
        return json_encode($this->normalizeCartForComparison($a)) === json_encode($this->normalizeCartForComparison($b));
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     * @return array<int, array{product_id:int, quantity:int, price:float, name:string, image:string}>
     */
    private function normalizeCartForComparison(array $cart): array
    {
        $rows = [];
        foreach ($cart as $item) {
            if (! isset($item['product_id'])) {
                continue;
            }
            $rows[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) ($item['quantity'] ?? 0),
                'price' => (float) ($item['price'] ?? 0),
                'name' => (string) ($item['name'] ?? ''),
                'image' => (string) ($item['image'] ?? ''),
            ];
        }
        usort($rows, fn ($x, $y) => $x['product_id'] <=> $y['product_id']);

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    private function sessionCartHasNonMinimalKeys(array $cart): bool
    {
        $allowed = ['product_id', 'name', 'price', 'quantity', 'image'];

        foreach ($cart as $item) {
            foreach (array_keys($item) as $key) {
                if (! in_array($key, $allowed, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getCartCount(): int
    {
        return count(Session::get('cart', []));
    }

    public function updateCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (! $product->isPurchasableByClient()) {
            return response()->json(['success' => false, 'message' => Product::MSG_CLIENT_AGOTADO], 400);
        }

        $requestedQty = (int) $request->quantity;
        $quantityApplied = min($requestedQty, (int) $product->stock_current);
        $stockClamped = $quantityApplied < $requestedQty;

        if ($quantityApplied < 1) {
            return response()->json([
                'success' => false,
                'message' => $product->stock_current < 1
                    ? Product::MSG_CLIENT_AGOTADO
                    : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
            ], 400);
        }

        $cart = Session::get('cart', []);
        $lineSubtotal = 0.0;
        $found = false;

        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $request->product_id) {
                $cart[$index]['quantity'] = $quantityApplied;
                // Keep only minimal keys so the session never retains inflated rows.
                $cart[$index] = [
                    'product_id' => (int) $item['product_id'],
                    'name' => (string) ($item['name'] ?? ''),
                    'price' => (float) $item['price'],
                    'quantity' => $quantityApplied,
                    'image' => (string) ($item['image'] ?? ''),
                ];
                $unitPrice = (float) $item['price'];
                $lineSubtotal = $unitPrice * $quantityApplied;
                $found = true;
                break;
            }
        }

        if (! $found) {
            return response()->json(['success' => false, 'message' => 'El producto no está en el carrito'], 404);
        }

        Session::put('cart', $cart);

        $authClient = Auth::guard('clients')->user();
        if ($authClient) {
            CartService::saveToDb($authClient->user_id, $cart);
        }

        return response()->json([
            'success' => true,
            'message' => 'Carrito actualizado',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
            'line_subtotal' => $lineSubtotal,
            'quantity_applied' => $quantityApplied,
            'stock_clamped' => $stockClamped,
        ]);
    }

    /**
     * Display cart. Session `cart` is the source of truth (minimal rows only).
     * It is only mutated via addToCart, updateCart, removeFromCart, or syncCartWithStock when stock clamps occur.
     */
    public function cart(Request $request)
    {
        $this->syncCartWithStock();

        $cart = Session::get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $item) {
            $product = Product::find($item['product_id']);

            // Rebuild cart rows using the latest product availability (display only).
            if ($product && $product->isPurchasableByClient()) {
                $qty = min((int) $item['quantity'], $product->stock_current);
                if ($qty < 1) {
                    continue;
                }

                $subtotal = $item['price'] * $qty;
                $total += $subtotal;

                $image = ProductImageUrls::clientPresentation($product);
                $cartItems[] = [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'price' => $item['price'],
                    'image_url' => $image['image_url'],
                    'uses_placeholder_image' => $image['uses_placeholder_image'],
                    'placeholder_icon_class' => $image['placeholder_icon_class'],
                    'quantity' => $qty,
                    'stock_available' => $product->stock_current,
                    'subtotal' => $subtotal,
                    'product_url' => $product->clientProductUrl(),
                ];
            }
        }

        $cartCount = $this->getCartCount();
        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $cartItemsPaginator = new LengthAwarePaginator(
            collect($cartItems)->forPage($currentPage, $perPage)->values()->all(),
            count($cartItems),
            $perPage,
            $currentPage,
            ['path' => route('clients.cart')]
        );
        $cartItemsPaginator->withQueryString();

        return view('client.cart', compact('cartItemsPaginator', 'total', 'cartCount'));
    }

    public function removeFromCart(int $id)
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío', 'cart_count' => 0, 'cart_total' => 0], 400);
        }

        $cart = array_values(array_filter($cart, fn ($item) => $item['product_id'] != $id));

        Session::put('cart', $cart);

        $authClient = Auth::guard('clients')->user();
        if ($authClient) {
            CartService::saveToDb($authClient->user_id, $cart);
        }

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    /** Empty the session cart (AJAX from cart page). */
    public function clearCart()
    {
        Session::put('cart', []);

        $authClient = Auth::guard('clients')->user();
        if ($authClient) {
            CartService::saveToDb($authClient->user_id, []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado',
            'cart_count' => 0,
            'cart_total' => 0.0,
        ]);
    }

    // Creates a pending web order and reserves stock immediately.
    public function checkout(Request $request, InventoryMovementService $inventoryService)
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío'], 400);
        }

        $validatedCheckout = $request->validate([
            'payment_method' => ['required', Rule::in(['cash', 'sinpe', 'transfer'])],
        ], [
            'payment_method.required' => 'Seleccione un método de pago.',
            'payment_method.in' => 'Método de pago no válido.',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $validatedItems = [];

            foreach ($cart as $item) {
                $product = Product::find($item['product_id']);

                if (! $product || ! $product->isPurchasableByClient()) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => Product::MSG_CLIENT_AGOTADO], 400);
                }

                $quantity = (int) ($item['quantity'] ?? 0);
                if ($quantity < 1) {
                    Log::warning('checkout_invalid_quantity', [
                        'product_id' => $item['product_id'] ?? null,
                        'raw_quantity' => $item['quantity'] ?? null,
                    ]);
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => 'Cantidad inválida en el carrito. Actualiza la página e inténtalo de nuevo.'], 400);
                }

                if ($product->stock_current < $quantity) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => $product->stock_current < 1
                            ? Product::MSG_CLIENT_AGOTADO
                            : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
                    ], 400);
                }

                $itemTotal = $item['price'] * $quantity;
                $subtotal += $itemTotal;

                $validatedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $item['price'],
                    'total' => $itemTotal,
                ];
            }

            Log::info('checkout_persisting_items', [
                'items' => collect($validatedItems)->map(fn ($i) => [
                    'product_id' => $i['product']->product_id,
                    'qty' => $i['quantity'],
                ])->values()->all(),
            ]);

            /** @var Client|null $client */
            $client = Auth::guard('clients')->user();

            $sale = Sale::create([
                'invoice_number' => (new Sale)->generateInvoiceNumber(),
                'client_id' => $client?->user_id,
                'sale_date' => now(),
                'payment_method' => $validatedCheckout['payment_method'],
                'status' => 'pending',
                'order_source' => 'web_cart',
                'subtotal' => $subtotal,
                'iva' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'notes' => 'Pedido realizado desde la tienda en línea',
            ]);

            foreach ($validatedItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $item['product']->product_id,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $item['price'],
                    'unit_discount' => 0,
                    'total' => $item['total'],
                ]);

                $inventoryService->recordWebCartSale(
                    product: $item['product'],
                    quantity: (int) $item['quantity'],
                    saleId: $sale->sale_id,
                );
            }

            Session::forget('cart');
            if ($client) {
                CartService::saveToDb($client->user_id, []);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'sale_id' => $sale->sale_id,
                'invoice_number' => $sale->invoice_number,
                'payment_method' => $sale->payment_method,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error al procesar el pedido: '.$e->getMessage()], 500);
        }
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

        $cartCount = $this->getCartCount();

        $invoiceCount = Sale::countActiveClientInvoices((int) $client->user_id);
        $unseenHistoryCount = Sale::countUnseenInClientHistory((int) $client->user_id);
        $invoicesRevision = Sale::clientInvoicesRevision((int) $client->user_id);

        return view('client.Invoices', compact(
            'orders',
            'cartCount',
            'invoiceCount',
            'unseenHistoryCount',
            'invoicesRevision',
            'tab',
            'pendingReviewProducts'
        ));
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

    public function notifications(Request $request)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $client->unreadNotifications->markAsRead();

        $cartCount = $this->getCartCount();

        $notifications = $client->notifications()
            ->latest()
            ->paginate(AdminPerPage::resolve($request->input('per_page', 10)))
            ->withQueryString();

        return view('client.notifications', compact('notifications', 'cartCount'));
    }

    public function showInvoice(Sale $sale)
    {
        $client = Auth::guard('clients')->user();

        if ((int) $sale->client_id !== (int) $client->user_id) {
            abort(404);
        }

        $sale->load(['saleItems.product']);

        $cartCount = $this->getCartCount();

        $invoiceCount = Sale::countActiveClientInvoices((int) $client->user_id);

        return view('client.invoice-detail', compact('sale', 'cartCount', 'invoiceCount'));
    }

    private function getCartTotal(): float
    {
        return array_reduce(
            Session::get('cart', []),
            fn ($carry, $item) => $carry + $item['price'] * $item['quantity'],
            0
        );
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
