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
}
