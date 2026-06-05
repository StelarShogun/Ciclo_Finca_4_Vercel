<?php

namespace App\Http\Controllers\Client\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Services\Client\Catalog\CatalogPayloadBuilder;
use App\Services\Client\Storefront\ClientCategoryIcons;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Services\Media\ProductImageUrls;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Home-page catalog helpers only. Catalog listing logic lives in
 * {@see CatalogPayloadBuilder} and related services.
 */
trait BuildsClientCatalogPages
{
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
}
