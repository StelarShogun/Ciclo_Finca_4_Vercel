<?php

namespace App\ViewModels\Client;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Client\Storefront\ClientCategoryIcons;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Services\Shared\Media\ProductImageUrls;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Arma el payload Inertia de la página de inicio (Client/Home/Index).
 * Mantiene al StorefrontController como simple orquestador.
 */
final class StorefrontViewModel
{
    /**
     * @return array<string, mixed>
     */
    public static function home(): array
    {
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

        $categories = self::cachedRootCategories();

        $reviewStats = ProductReview::aggregatesForProductIds(
            $featuredProducts->pluck('product_id')->map(fn ($id) => (int) $id)->all()
        );

        return [
            'featuredProducts' => $featuredProducts
                ->map(fn (Product $product): array => self::productPayload($product, $reviewStats))
                ->values(),
            'categories' => $categories
                ->map(fn (Category $category): array => self::categoryPayload($category))
                ->values(),
            'showGuestRegisterCta' => ! Auth::guard('clients')->check() && ! session('admin_catalog_mode'),
            'hero' => [
                'title' => 'Equípate para rodar',
                'emphasis' => 'con asesoría real en tienda',
                'subtitle' => 'Bicicletas, componentes y accesorios listos para encargo con retiro rápido.',
                'description' => 'Te guiamos en elección, ajuste y preparación para que retires con confianza.',
            ],
        ];
    }

    /**
     * @param  array<int, array{avg: float|int|string|null, count: int|string|null}>  $reviewStats
     * @return array<string, mixed>
     */
    private static function productPayload(Product $product, array $reviewStats): array
    {
        $picture = ProductImageUrls::cardPicture($product);
        $stats = $reviewStats[(int) $product->product_id] ?? null;

        return [
            'id' => (int) $product->product_id,
            'name' => (string) $product->name,
            'description' => $product->description ? Str::limit((string) $product->description, 80) : null,
            'category' => $product->category?->name ?? 'Uncategorized',
            'price' => (float) $product->sale_price,
            'priceFormatted' => '₡'.number_format((float) $product->sale_price, 0, ',', '.'),
            'stockCurrent' => (int) ($product->stock_current ?? 0),
            'stockLabel' => $product->clientCatalogStockLabel(),
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
                'avg' => (float) data_get($stats, 'avg', 0),
                'count' => (int) data_get($stats, 'count', 0),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function categoryPayload(Category $category): array
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

    /** Árbol de categorías raíz + hijos (cacheado). */
    private static function cachedRootCategories(): Collection
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
}
