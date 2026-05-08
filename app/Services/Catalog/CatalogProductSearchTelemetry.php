<?php

namespace App\Services\Catalog;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registers catalog search impressions: each page load with an active search query
 * counts one hit per product shown on that results page (CF4-108).
 */
final class CatalogProductSearchTelemetry
{
    private const MIN_QUERY_LENGTH = 2;

    private const MAX_QUERY_STORED = 255;

    /** @param  LengthAwarePaginator<int, \App\Models\Product>  $paginator */
    public static function recordSearchResultsPage(string $rawSearch, LengthAwarePaginator $paginator): void
    {
        $normalized = trim(mb_substr($rawSearch, 0, self::MAX_QUERY_STORED));
        if (mb_strlen($normalized) < self::MIN_QUERY_LENGTH) {
            return;
        }

        $productIds = $paginator->getCollection()
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return;
        }

        $now = now();

        try {
            foreach ($productIds->chunk(200) as $chunk) {
                $rows = [];
                foreach ($chunk as $productId) {
                    $rows[] = [
                        'product_id' => (int) $productId,
                        'query_normalized' => $normalized,
                        'created_at' => $now,
                    ];
                }
                DB::table('catalog_product_search_events')->insert($rows);
            }
        } catch (\Throwable $e) {
            Log::warning('catalog search telemetry insert failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
