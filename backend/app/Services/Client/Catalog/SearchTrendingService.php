<?php

namespace App\Services\Client\Catalog;

use App\Models\Product;
use App\Services\Admin\CatalogMostSearchedProductsReportQuery;
use App\Services\Shared\Media\ProductImageUrls;

final class SearchTrendingService
{
    public function payload(string $period, int $limit): array
    {
        $productWith = [
            'category',
            'media' => static fn ($query) => $query->where('collection_name', 'main_image'),
        ];
        $productScores = CatalogSearchTrendingQuery::toppedActiveProductScores($period, $limit);
        $products = collect();

        if ($productScores->isNotEmpty()) {
            $order = $productScores->pluck('product_id')->all();
            $found = Product::query()
                ->with($productWith)
                ->whereIn('product_id', $order)
                ->activeInClientStore()
                ->get();

            $products = collect($order)
                ->map(fn (int $id) => $found->firstWhere('product_id', $id))
                ->filter()
                ->values();
        }

        $terms = CatalogSearchTrendingQuery::topNormalizedTerms($period, $limit);
        $fallback = CatalogSearchTrendingQuery::latestActiveProductsForFallback($limit)->values();
        $useFallbackProducts = $products->isEmpty() && $terms->isEmpty() && $fallback->isNotEmpty();

        if ($useFallbackProducts) {
            $products = $fallback;
            $terms = collect();
        }

        return array_merge($this->periodMeta($period), [
            'limit' => $limit,
            'is_fallback' => $useFallbackProducts,
            'products' => $products->map(fn (Product $product) => $this->productRow($product, $useFallbackProducts))->values()->all(),
            'terms' => $terms->map(function (object $row): array {
                $term = trim((string) $row->term);

                return [
                    'type' => 'term',
                    'name' => $term,
                    'sku' => null,
                    'category' => null,
                    'image_url' => null,
                    'match_type' => 'trending_term',
                    'url' => route('clients.catalog', ['search' => $term]),
                ];
            })->values()->all(),
        ]);
    }

    public function periodMeta(string $period): array
    {
        return [
            'period' => [
                'key' => $period,
                'label_es' => CatalogSearchTrendingQuery::periodLabelEs($period),
                'window_start' => CatalogMostSearchedProductsReportQuery::periodStart($period)->toIso8601String(),
            ],
        ];
    }

    private function productRow(Product $product, bool $fallback): array
    {
        $image = ProductImageUrls::clientPresentation($product);

        return [
            'type' => 'product',
            'id' => (int) $product->product_id,
            'name' => (string) $product->name,
            'sku' => Product::skuFromId((int) $product->product_id),
            'category' => $product->category !== null ? (string) $product->category->name : '',
            'image_url' => $image['image_url'],
            'uses_placeholder_image' => $image['uses_placeholder_image'],
            'placeholder_icon_class' => $image['placeholder_icon_class'],
            'match_type' => $fallback ? 'featured' : 'trending',
            'url' => route('clients.product', [
                'id' => (int) $product->product_id,
                'slug' => $product->clientPublicSlug(),
            ]),
        ];
    }
}
