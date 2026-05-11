<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Aggregates catalog search impressions for admin reporting (CF4-108). */
final class CatalogMostSearchedProductsReportQuery
{
    /** @param  '7d'|'30d'|'90d'  $period */
    public static function periodStart(string $period): Carbon
    {
        return match ($period) {
            '7d' => Carbon::now()->subDays(6)->startOfDay(),
            '90d' => Carbon::now()->subDays(89)->startOfDay(),
            default => Carbon::now()->subDays(29)->startOfDay(),
        };
    }

    /**
     * @param  '7d'|'30d'|'90d'  $period
     * @return Collection<int, object{product_id:int,name:string,hit_count:int}>
     */
    public static function topProducts(string $period, int $limit = 50): Collection
    {
        $start = self::periodStart($period);

        return DB::table('catalog_product_search_events as e')
            ->join('products as p', 'p.product_id', '=', 'e.product_id')
            ->where('e.created_at', '>=', $start)
            ->groupBy('p.product_id', 'p.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(max(1, min($limit, 100)))
            ->selectRaw('p.product_id, p.name, COUNT(*) as hit_count')
            ->get();
    }

    /** @param  '7d'|'30d'|'90d'  $period */
    public static function totalEventsSince(string $period): int
    {
        $start = self::periodStart($period);

        return (int) DB::table('catalog_product_search_events')
            ->where('created_at', '>=', $start)
            ->count();
    }

    /** Distinct products that appeared in catalog search results in the period. */
    public static function distinctProductCount(string $period): int
    {
        $start = self::periodStart($period);

        return (int) DB::table('catalog_product_search_events')
            ->where('created_at', '>=', $start)
            ->selectRaw('COUNT(DISTINCT product_id) as c')
            ->value('c');
    }
}
