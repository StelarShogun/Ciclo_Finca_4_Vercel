<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\BuildsClientCatalogPages;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FavoriteProduct;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Catalog\CatalogProductSearchTelemetry;
use App\Support\AdminPerPage;
use App\Support\ClientStorefrontCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class StorefrontController extends Controller
{
    use BuildsClientCatalogPages;

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
}
