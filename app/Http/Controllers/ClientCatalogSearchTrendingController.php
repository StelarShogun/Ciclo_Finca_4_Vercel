<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Admin\CatalogMostSearchedProductsReportQuery;
use App\Services\Catalog\CatalogSearchTrendingQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ClientCatalogSearchTrendingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $period = $this->validatedPeriod((string) $request->query('period', '30d'));
        $limit = $this->validatedLimit((int) $request->query('limit', 8));

        try {
            $hasMediaTable = Schema::hasTable('media');

            $productWith = ['category'];
            if ($hasMediaTable) {
                $productWith['media'] = static function ($q) {
                    $q->where('collection_name', 'main_image');
                };
            }

            $productScores = CatalogSearchTrendingQuery::toppedActiveProductScores($period, $limit);

            /** @var Collection<int,Product> $products */
            $products = collect();
            if ($productScores->isNotEmpty()) {
                $order = $productScores->pluck('product_id')->all();
                $products = Product::query()
                    ->with($productWith)
                    ->whereIn('product_id', $order)
                    ->activeInClientStore()
                    ->get();

                // Preserve popularity order returned by telemetry.
                $products = collect($order)
                    ->map(fn (int $id) => $products->firstWhere('product_id', $id))
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

            $productRows = $products->map(fn (Product $p) => $this->productRow($p, $hasMediaTable, $useFallbackProducts));

            $termRows = $terms->map(function (object $row) {
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
            })->values()->all();

            return response()->json(array_merge($this->periodMeta($period), [
                'limit' => $limit,
                'is_fallback' => $useFallbackProducts,
                'products' => $productRows->values()->all(),
                'terms' => $termRows,
            ]));
        } catch (\Throwable $e) {
            Log::error('Catalog search trending failed', [
                'period' => $period,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json(array_merge($this->periodMeta($period), [
                'error' => 'temporary_unavailable',
                'limit' => $limit,
                'is_fallback' => false,
                'products' => [],
                'terms' => [],
            ]), 500);
        }
    }

    /**
     * @return array{period: array{key: string, label_es: string, window_start: string}}
     */
    private function periodMeta(string $period): array
    {
        $start = CatalogMostSearchedProductsReportQuery::periodStart($period);

        return [
            'period' => [
                'key' => $period,
                'label_es' => CatalogSearchTrendingQuery::periodLabelEs($period),
                'window_start' => $start->toIso8601String(),
            ],
        ];
    }

    /** @param  '7d'|'30d'|'90d'  $period */
    private function validatedPeriod(string $period): string
    {
        return in_array($period, ['7d', '30d', '90d'], true) ? $period : '30d';
    }

    private function validatedLimit(int $limit): int
    {
        if ($limit < 1) {
            return 8;
        }
        if ($limit > 10) {
            return 10;
        }

        return $limit;
    }

    private function productRow(Product $p, bool $hasMediaTable, bool $fallback): array
    {
        $sku = Product::skuFromId((int) $p->product_id);
        $imageUrl = '';
        if ($hasMediaTable) {
            try {
                $imageUrl = (string) $p->getFirstMediaUrl('main_image');
            } catch (\Throwable $e) {
                $imageUrl = '';
            }
        }
        if ($imageUrl === '') {
            $imageUrl = (string) asset('assets/images/products/'.($p->image ?? 'default.png'));
        }

        return [
            'type' => 'product',
            'id' => (int) $p->product_id,
            'name' => (string) $p->name,
            'sku' => $sku,
            'category' => $p->category !== null ? (string) $p->category->name : '',
            'image_url' => $imageUrl,
            'match_type' => $fallback ? 'featured' : 'trending',
            'url' => route('clients.product', [
                'id' => (int) $p->product_id,
                'slug' => $p->clientPublicSlug(),
            ]),
        ];
    }
}
