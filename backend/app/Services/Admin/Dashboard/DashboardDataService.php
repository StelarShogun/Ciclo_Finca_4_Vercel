<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Support\DashboardTodaySales;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DashboardDataService
{
    private const TABLE_LIMIT = 10;

    public function summary(): array
    {
        if (config('app.debug')) {
            Log::debug('Categorías en DB: '.Category::count());
        }

        $monthlySales = Sale::whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->where('status', 'completed')
            ->sum('total');

        $lastMonthSales = Sale::whereMonth('sale_date', Carbon::now()->subMonth()->month)
            ->whereYear('sale_date', Carbon::now()->subMonth()->year)
            ->where('status', 'completed')
            ->sum('total');

        return [
            'totalProducts' => Product::count(),
            'totalSuppliers' => Supplier::count(),
            'totalCategories' => Category::count(),
            'todaySales' => DashboardTodaySales::sumToday(),
            'lowStockProducts' => Product::lowStockAlert()->count(),
            'lowStockProductsList' => Product::with(['category', 'supplier'])
                ->lowStockAlert()
                ->orderBy('stock_current')
                ->limit(self::TABLE_LIMIT)
                ->get(),
            'recentSales' => Sale::with(['client'])
                ->orderByDesc('sale_date')
                ->limit(self::TABLE_LIMIT)
                ->get(),
            // salesByDay lo aporta withRequestRange() según el rango de la petición;
            // no se calcula aquí para no desperdiciar la consulta dentro del caché.
            'productsByCategory' => Category::withCount(['products' => fn ($query) => $query->where('status', 'active')])
                ->orderByDesc('products_count')
                ->get()
                ->map(fn (Category $category) => [
                    'categoria' => $category->name,
                    'total' => $category->products_count,
                ]),
            'salesTrend' => DashboardTodaySales::salesTrendPercent(),
            'monthlySales' => $monthlySales,
            'monthlyTrend' => $this->trend($monthlySales, $lastMonthSales),
            'topProducts' => DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.product_id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
                ->where('sales.status', 'completed')
                ->where('sales.sale_date', '>=', Carbon::now()->subDays(30))
                ->select(
                    'products.name',
                    'products.image',
                    DB::raw('SUM(sale_items.quantity) as total_vendido'),
                    DB::raw('SUM(sale_items.total) as ingresos')
                )
                ->groupBy('products.product_id', 'products.name', 'products.image')
                ->orderByDesc('total_vendido')
                ->limit(5)
                ->get(),
            'topSuppliers' => Supplier::withCount('products')
                ->orderByDesc('products_count')
                ->limit(5)
                ->get(),
        ];
    }

    public function jsonSummary(): array
    {
        return [
            'success' => true,
            'totalProducts' => Product::count(),
            'totalSuppliers' => Supplier::count(),
            'totalCategories' => Category::count(),
            'todaySales' => DashboardTodaySales::sumToday(),
            'salesTrend' => DashboardTodaySales::salesTrendPercent(),
            'lowStockProducts' => Product::whereColumn('stock_current', '<', 'stock_minimum')->count(),
        ];
    }

    public function chartData(string $period): array
    {
        $startDate = $this->startDate($period)->startOfDay();

        $salesRows = Sale::query()
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(total) as total'))
            ->where('sale_date', '>=', $startDate)
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->orderBy('date')
            ->get();

        return [
            'sales' => $this->fillSalesChartSeries($salesRows, $startDate, Carbon::now()->startOfDay()),
            'categories' => Category::withCount(['products' => fn ($query) => $query->where('status', 'active')])
                ->orderByDesc('products_count')
                ->get()
                ->map(fn (Category $category) => [
                    'categoria' => $category->name,
                    'total' => $category->products_count,
                ])
                ->all(),
        ];
    }

    public function withRequestRange(array $data, Request $request): array
    {
        [$salesFrom, $salesTo, $salesRange] = $this->resolveSalesRange($request);

        $data['todaySales'] = DashboardTodaySales::sumToday();
        $data['salesTrend'] = DashboardTodaySales::salesTrendPercent();
        $data['salesByDay'] = $this->salesSeries($salesFrom, $salesTo);
        $data['salesRange'] = $salesRange;
        $data['salesFrom'] = $salesFrom->toDateString();
        $data['salesTo'] = $salesTo->toDateString();

        return $data;
    }

    public function salesSeries(Carbon $from, Carbon $to): array
    {
        $rows = Sale::query()
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('COALESCE(SUM(total), 0) as total'))
            ->whereBetween('sale_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->orderBy('date')
            ->get();

        return $this->fillSalesChartSeries($rows, $from, $to);
    }

    public function chartPeriodLabel(string $period): string
    {
        return match ($period) {
            '30d' => 'últimos 30 días',
            '90d' => 'últimos 90 días',
            default => 'últimos 7 días',
        };
    }

    public function startDate(string $period): Carbon
    {
        return match ($period) {
            '30d' => Carbon::now()->subDays(29),
            '90d' => Carbon::now()->subDays(89),
            default => Carbon::now()->subDays(6),
        };
    }

    private function resolveSalesRange(Request $request): array
    {
        $today = Carbon::today();

        if ($request->query('range') === 'last15') {
            return [$today->copy()->subDays(14), $today->copy(), 'last15'];
        }

        if ($request->query('range') === 'last30') {
            return [$today->copy()->subDays(29), $today->copy(), 'last30'];
        }

        if ($request->query('range') === 'month') {
            return [$today->copy()->startOfMonth(), $today->copy(), 'month'];
        }

        if ($request->query('range') === 'custom') {
            $from = $this->parseDate($request->query('from'));
            $to = $this->parseDate($request->query('to'));

            if ($from && $to) {
                if ($from->gt($to)) {
                    [$from, $to] = [$to, $from];
                }

                if ($from->diffInDays($to) > 92) {
                    $from = $to->copy()->subDays(92);
                }

                return [$from, $to, 'custom'];
            }
        }

        return [$today->copy()->subDays(6), $today->copy(), 'last7'];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function fillSalesChartSeries(iterable $rows, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $byDate = [];
        foreach ($rows as $row) {
            $key = substr((string) data_get($row, 'date'), 0, 10);
            $byDate[$key] = (float) data_get($row, 'total');
        }

        $out = [];
        $cursor = $rangeStart->copy()->startOfDay();
        $end = $rangeEnd->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $out[] = ['date' => $key, 'total' => $byDate[$key] ?? 0.0];
            $cursor->addDay();
        }

        return $out;
    }

    private function trend(float|int $current, float|int $previous): float|int
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
