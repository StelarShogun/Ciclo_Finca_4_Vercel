<?php

namespace App\Services\Admin\Reports;

use App\Models\SaleItem;
use App\Support\AdminDateRange;
use Illuminate\Http\Request;

final class CategorySalesReportService
{
    public function payload(Request $request, array $validated): array
    {
        $dateRange = $validated['date_range'] ?? 'month';
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        [$from, $to] = AdminDateRange::boundsAsDateTimeStrings($dateRange, $dateFrom, $dateTo, storedAsUtc: true);

        $rows = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('categories', 'products.category_id', '=', 'categories.category_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy('categories.category_id', 'categories.name')
            ->selectRaw('
                categories.category_id,
                categories.name          AS category_name,
                SUM(sale_items.quantity) AS total_units,
                SUM(sale_items.total)    AS total_revenue
            ')
            ->orderByDesc('total_revenue')
            ->get();

        $grandTotal = $rows->sum('total_revenue');

        $rows->transform(function ($row) use ($grandTotal) {
            $row->percentage = $grandTotal > 0
                ? round(($row->total_revenue / $grandTotal) * 100, 1)
                : 0;

            return $row;
        });

        return [
            'rows' => $rows->map(fn ($row): array => [
                'category_id' => (int) $row->category_id,
                'category_name' => (string) $row->category_name,
                'total_units' => (int) $row->total_units,
                'total_revenue' => (float) $row->total_revenue,
                'percentage' => (float) $row->percentage,
            ])->values()->all(),
            'grandTotal' => (float) $grandTotal,
            'totalUnits' => (int) $rows->sum('total_units'),
            'chartData' => $rows->map(fn ($row): array => [
                'label' => $row->category_name,
                'value' => $row->total_revenue,
                'percent' => $row->percentage,
            ])->values()->all(),
            'filters' => [
                'date_range' => (string) $dateRange,
                'date_from' => (string) ($request->input('date_from') ?? ''),
                'date_to' => (string) ($request->input('date_to') ?? ''),
            ],
        ];
    }
}
