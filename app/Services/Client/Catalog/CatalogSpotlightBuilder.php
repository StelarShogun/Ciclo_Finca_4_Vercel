<?php

namespace App\Services\Client\Catalog;

use App\Models\Product;
use App\Services\Client\Storefront\ClientStorefrontCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CatalogSpotlightBuilder
{
    /**
     * @return Collection<int, array{product: Product, spotlight: string}>
     */
    public function rows(): Collection
    {
        $ttl = ClientStorefrontCache::ttlSeconds((int) config('cf4_performance.client_catalog_spotlight_ttl', 60));

        return Cache::remember(ClientStorefrontCache::KEY_CATALOG_SPOTLIGHT, $ttl, function () {
            return $this->rowsUncached();
        });
    }

    /**
     * @return Collection<int, array{product: Product, spotlight: string}>
     */
    private function rowsUncached(): Collection
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
