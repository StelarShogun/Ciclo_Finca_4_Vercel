<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Services\Admin\CatalogMostSearchedProductsReportQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Reads aggregated catalog search metrics for client-facing trending (CF4-107). */
final class CatalogSearchTrendingQuery
{
    /** @param  '7d'|'30d'|'90d'  $period */
    public static function periodLabelEs(string $period): string
    {
        return match ($period) {
            '7d' => 'Últimos 7 días',
            '90d' => 'Últimos 90 días',
            default => 'Últimos 30 días',
        };
    }

    /**
     * @param  '7d'|'30d'|'90d'  $period
     * @return Collection<int, object{product_id:int, hit_count:int}>
     */
    public static function toppedActiveProductScores(string $period, int $limit): Collection
    {
        $start = CatalogMostSearchedProductsReportQuery::periodStart($period);
        $limit = max(1, min($limit, 10));

        $okPlaceholders = implode(',', array_fill(0, 2, '?'));

        return DB::table('catalog_product_search_events as e')
            ->join('products as p', 'p.product_id', '=', 'e.product_id')
            ->where('e.created_at', '>=', $start)
            ->whereRaw('LOWER(TRIM(COALESCE(p.status, \'\'))) IN ('.$okPlaceholders.')', ['active', 'activo'])
            ->groupBy('p.product_id')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit($limit)
            ->selectRaw('p.product_id, COUNT(*) as hit_count')
            ->get();
    }

    /**
     * Popular normalized search phrases (aggregated counts only).
     *
     * @param  '7d'|'30d'|'90d'  $period
     * @return Collection<int, object{term:string, hit_count:int}>
     */
    public static function topNormalizedTerms(string $period, int $limit): Collection
    {
        $start = CatalogMostSearchedProductsReportQuery::periodStart($period);
        $limit = max(1, min($limit, 10));

        return DB::table('catalog_product_search_events')
            ->where('created_at', '>=', $start)
            ->whereNotNull('query_normalized')
            ->whereRaw('LENGTH(TRIM(query_normalized)) >= 2')
            ->groupBy('query_normalized')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit($limit)
            ->selectRaw('query_normalized AS term, COUNT(*) AS hit_count')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public static function latestActiveProductsForFallback(int $limit): Collection
    {
        $limit = max(1, min($limit, 10));

        $with = ['category'];
        if (Schema::hasTable('media')) {
            $with['media'] = static function ($q) {
                $q->where('collection_name', 'main_image');
            };
        }

        return Product::query()
            ->with($with)
            ->activeInClientStore()
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }
}
