<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SalesPerformanceMetricsService
{
    /**
     * Aggregates completed sales in [start, end] (inclusive, by sale_date).
     *
     * @return array{sales_count:int, revenue:float}
     */
    public function aggregateCompletedSales(CarbonInterface $start, CarbonInterface $end): array
    {
        $row = DB::table('sales')
            ->where('status', 'completed')
            ->where('sale_date', '>=', $start->format('Y-m-d H:i:s'))
            ->where('sale_date', '<=', $end->format('Y-m-d H:i:s'))
            ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(total), 0) as revenue')
            ->first();

        return [
            'sales_count' => (int) ($row->sales_count ?? 0),
            'revenue' => round((float) ($row->revenue ?? 0), 2),
        ];
    }

    /**
     * @param  array{sales_count:int, revenue:float}  $current
     * @param  array{sales_count:int, revenue:float}  $previous
     * @return array{
     *   revenue_change_percent: float|null,
     *   sales_count_change_percent: float|null,
     *   revenue_trend: 'up'|'down'|'flat',
     *   sales_count_trend: 'up'|'down'|'flat',
     *   revenue_percent_not_comparable: bool
     * }
     */
    public function comparisonVersusPrior(array $current, array $previous): array
    {
        $cRev = (float) $current['revenue'];
        $pRev = (float) $previous['revenue'];
        $cCnt = (int) $current['sales_count'];
        $pCnt = (int) $previous['sales_count'];

        $revNotComparable = $pRev <= 0.0 && $cRev > 0.0;
        $cntNotComparable = $pCnt === 0 && $cCnt > 0;

        return [
            'revenue_change_percent' => $this->percentChange($cRev, $pRev),
            'sales_count_change_percent' => $this->percentChangeInt($cCnt, $pCnt),
            'revenue_trend' => $this->trendFloat($cRev, $pRev),
            'sales_count_trend' => $this->trendInt($cCnt, $pCnt),
            'revenue_percent_not_comparable' => $revNotComparable,
            'sales_count_percent_not_comparable' => $cntNotComparable,
        ];
    }

    private function percentChange(float $current, float $previous): ?float
    {
        if ($previous > 0.0) {
            return round((($current - $previous) / $previous) * 100, 2);
        }
        if ($previous <= 0.0 && $current > 0.0) {
            return null;
        }

        return 0.0;
    }

    private function percentChangeInt(int $current, int $previous): ?float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 2);
        }
        if ($previous === 0 && $current > 0) {
            return null;
        }

        return 0.0;
    }

    /**
     * @return 'up'|'down'|'flat'
     */
    private function trendFloat(float $current, float $previous): string
    {
        if ($current > $previous) {
            return 'up';
        }
        if ($current < $previous) {
            return 'down';
        }

        return 'flat';
    }

    /**
     * @return 'up'|'down'|'flat'
     */
    private function trendInt(int $current, int $previous): string
    {
        if ($current > $previous) {
            return 'up';
        }
        if ($current < $previous) {
            return 'down';
        }

        return 'flat';
    }
}
