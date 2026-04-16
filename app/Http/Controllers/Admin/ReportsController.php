<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesPerformanceRangeRequest;
use App\Models\Product;
use App\Services\SalesPerformanceDateRangeService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    private const PERIODS = ['7d', '30d', '90d'];

    private const TOP10_METRICS = ['revenue', 'units'];

    public function index()
    {
        return view('admin.reports.index');
    }

    /**
     * CF4-24 Fase 1: resolve selected period + equivalent previous period.
     */
    public function salesPerformanceRange(SalesPerformanceRangeRequest $request, SalesPerformanceDateRangeService $rangeService)
    {
        $resolved = $rangeService->resolve($request->validated());

        return response()->json([
            'success' => true,
            'preset' => $resolved['preset'],
            'from' => $resolved['from'],
            'to' => $resolved['to'],
            'current_period' => [
                'start' => $resolved['current_start']->toIso8601String(),
                'end' => $resolved['current_end']->toIso8601String(),
                'label' => $this->humanRangeLabel($resolved['current_start'], $resolved['current_end']),
            ],
            'previous_period' => [
                'start' => $resolved['previous_start']->toIso8601String(),
                'end' => $resolved['previous_end']->toIso8601String(),
                'label' => $this->humanRangeLabel($resolved['previous_start'], $resolved['previous_end']),
            ],
        ]);
    }

    public function productSales(Request $request)
    {
        return view('admin.reports.product-sales', [
            'period' => $this->normalizePeriod($request->query('period')),
            'sort' => $this->normalizeSort($request->query('sort')),
            'dir' => $this->normalizeDir($request->query('dir')),
            'q' => $this->normalizeQuery($request->query('q')),
            'top10' => $this->normalizeTop10Metric($request->query('top10')),
        ]);
    }

    public function productSalesTable(Request $request)
    {
        $period = $this->normalizePeriod($request->query('period'));
        $sort = $this->normalizeSort($request->query('sort'));
        $dir = $this->normalizeDir($request->query('dir'));
        $q = $this->normalizeQuery($request->query('q'));
        $top10Metric = $this->normalizeTop10Metric($request->query('top10'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        $start = $this->periodStart($period);

        $top10SortColumn = $top10Metric === 'units' ? 'units_sold' : 'revenue';
        $top10 = $this->newProductSalesQuery($start, $q)
            ->orderByDesc($top10SortColumn)
            ->limit(10)
            ->get();

        $sortColumn = $sort === 'units' ? 'units_sold' : 'revenue';
        $paginator = $this->newProductSalesQuery($start, $q)
            ->orderBy($sortColumn, $dir)
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->setPath(route('admin.reports.product-sales'));
        $paginator->appends([
            'period' => $period,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q !== '' ? $q : null,
            'top10' => $top10Metric,
        ]);

        $formatRow = function ($row) {
            return [
                'product_id' => (int) $row->product_id,
                'name' => (string) $row->name,
                'sku' => Product::skuFromId((int) $row->product_id),
                'units_sold' => (int) $row->units_sold,
                'revenue' => round((float) $row->revenue, 2),
            ];
        };

        return response()->json([
            'success' => true,
            'period' => $period,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q,
            'top10_metric' => $top10Metric,
            'top10' => $top10->map($formatRow)->values(),
            'rows' => collect($paginator->items())->map($formatRow)->values(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'pagination_html' => view('components.pagination', [
                'paginator' => $paginator,
                'label' => 'reporte',
            ])->render(),
        ]);
    }

    private function newProductSalesQuery(Carbon $start, string $q): Builder
    {
        $skuExpr = "CONCAT('BK-', LPAD(products.product_id, 3, '0'))";

        $query = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.sale_date', '>=', $start)
            ->select(
                'products.product_id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('SUM(sale_items.total) as revenue'),
            )
            ->groupBy('products.product_id', 'products.name');

        if ($q !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->havingRaw("(products.name LIKE ? OR {$skuExpr} LIKE ?)", [$like, $like]);
        }

        return $query;
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            '7d' => Carbon::now()->subDays(6)->startOfDay(),
            '90d' => Carbon::now()->subDays(89)->startOfDay(),
            default => Carbon::now()->subDays(29)->startOfDay(),
        };
    }

    private function normalizePeriod(mixed $value): string
    {
        $v = is_string($value) ? $value : '';

        return in_array($v, self::PERIODS, true) ? $v : '30d';
    }

    private function normalizeSort(mixed $value): string
    {
        $v = is_string($value) ? $value : '';

        return $v === 'units' ? 'units' : 'revenue';
    }

    private function normalizeDir(mixed $value): string
    {
        $v = is_string($value) ? strtolower($value) : '';

        return $v === 'asc' ? 'asc' : 'desc';
    }

    private function normalizeQuery(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }
        $t = trim($value);

        return mb_substr($t, 0, 100);
    }

    private function normalizeTop10Metric(mixed $value): string
    {
        $v = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($v, self::TOP10_METRICS, true) ? $v : 'revenue';
    }

    private function humanRangeLabel(CarbonInterface $start, CarbonInterface $end): string
    {
        return $start->format('d/m/Y').' - '.$end->format('d/m/Y');
    }
}
