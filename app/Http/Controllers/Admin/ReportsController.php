<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesPerformanceRangeRequest;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\ProductSalesReportQuery;
use App\Services\Admin\ReportPdfFilename;
use App\Services\SalesPerformanceDateRangeService;
use App\Services\SalesPerformanceMetricsService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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
     * Centro único de exportación (PDF y datos) desde el módulo Reportes.
     * Los enlaces respetan querystring opcional (p. ej. filtros de inventario o ventas pasados desde esas pantallas).
     */
    public function exports()
    {
        return view('admin.reports.exports');
    }

    /**
     * CF4-24: admin UI — sales totals and comparison by date range (loads metrics via JSON).
     */
    public function salesPerformance(Request $request)
    {
        $allowed = ['today', 'week', 'month', 'year', 'custom'];
        $preset = (string) $request->query('preset', 'month');
        if (! in_array($preset, $allowed, true)) {
            $preset = 'month';
        }
        $from = $request->query('from');
        $to = $request->query('to');

        return view('admin.reports.sales-performance', [
            'initialPreset' => $preset,
            'initialFrom' => is_string($from) ? $from : '',
            'initialTo' => is_string($to) ? $to : '',
        ]);
    }

    /**
     * CF4-24: resolve selected period + equivalent previous period.
     */
    public function salesPerformanceRange(SalesPerformanceRangeRequest $request, SalesPerformanceDateRangeService $rangeService)
    {
        $resolved = $rangeService->resolve($request->validated());

        return response()->json($this->salesPerformanceRangePayload($resolved));
    }

    /**
     * CF4-24: completed sales totals for current and prior equivalent period + comparison.
     */
    public function salesPerformanceMetrics(
        SalesPerformanceRangeRequest $request,
        SalesPerformanceDateRangeService $rangeService,
        SalesPerformanceMetricsService $metricsService,
    ) {
        $resolved = $rangeService->resolve($request->validated());
        $current = $metricsService->aggregateCompletedSales(
            $resolved['current_start'],
            $resolved['current_end'],
        );
        $previous = $metricsService->aggregateCompletedSales(
            $resolved['previous_start'],
            $resolved['previous_end'],
        );
        $comparison = $metricsService->comparisonVersusPrior($current, $previous);

        return response()->json(array_merge($this->salesPerformanceRangePayload($resolved), [
            'current_metrics' => $current,
            'previous_metrics' => $previous,
            'comparison' => $comparison,
        ]));
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function salesPerformanceRangePayload(array $resolved): array
    {
        return [
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
        ];
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
        $top10 = ProductSalesReportQuery::base($start, $q)
            ->orderByDesc($top10SortColumn)
            ->limit(10)
            ->get();

        $sortColumn = $sort === 'units' ? 'units_sold' : 'revenue';
        $paginator = ProductSalesReportQuery::base($start, $q)
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
            return ProductSalesReportQuery::formatRow($row);
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

    public function productSalesPdf(Request $request)
    {
        $period = $this->normalizePeriod($request->query('period'));
        $sort = $this->normalizeSort($request->query('sort'));
        $dir = $this->normalizeDir($request->query('dir'));
        $q = $this->normalizeQuery($request->query('q'));
        $top10Metric = $this->normalizeTop10Metric($request->query('top10'));

        $start = $this->periodStart($period);
        $top10SortColumn = $top10Metric === 'units' ? 'units_sold' : 'revenue';
        $sortColumn = $sort === 'units' ? 'units_sold' : 'revenue';

        $top10 = ProductSalesReportQuery::base($start, $q)
            ->orderByDesc($top10SortColumn)
            ->limit(10)
            ->get();

        $maxRows = AdminPdfExportLimits::PRODUCT_SALES_TABLE_MAX_ROWS;
        $tableRows = ProductSalesReportQuery::base($start, $q)
            ->orderBy($sortColumn, $dir)
            ->limit($maxRows)
            ->get();

        $totalMatching = (int) DB::query()
            ->fromSub(ProductSalesReportQuery::base($start, $q), 'product_sales_agg')
            ->count();

        $filterLines = [
            'Periodo: '.$this->periodLabel($period),
            'Top 10 por: '.($top10Metric === 'units' ? 'unidades' : 'ingresos'),
            'Tabla ordenada por: '.($sort === 'units' ? 'unidades' : 'ingresos').' ('.$dir.')',
        ];
        if ($q !== '') {
            $filterLines[] = 'Búsqueda: '.$q;
        }
        if ($totalMatching > $maxRows) {
            $filterLines[] = 'Nota: la tabla del PDF incluye como máximo '.$maxRows.' filas ('.$totalMatching.' productos con ventas en el periodo).';
        }

        $top10Formatted = $top10->map(fn ($row) => ProductSalesReportQuery::formatRow($row));
        $tableFormatted = $tableRows->map(fn ($row) => ProductSalesReportQuery::formatRow($row));

        $maxBarUnits = max(1, (int) $top10Formatted->max('units_sold'));
        $maxBarRevenue = max(1.0, (float) $top10Formatted->max('revenue'));

        $logoPath = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        $pdf = PDF::loadView('admin.reports.product-sales-pdf', [
            'period' => $period,
            'top10Metric' => $top10Metric,
            'top10' => $top10Formatted,
            'tableRows' => $tableFormatted,
            'maxBarUnits' => $maxBarUnits,
            'maxBarRevenue' => $maxBarRevenue,
            'pdfTitle' => 'Productos más vendidos',
            'pdfSubtitle' => 'Ventas completadas — Ciclo Finca 4',
            'logoPath' => is_file($logoPath) ? $logoPath : null,
            'filterLines' => $filterLines,
            'generatedFor' => 'Administración',
        ]);

        return $pdf->download(ReportPdfFilename::make('productos-vendidos'));
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            '7d' => 'últimos 7 días',
            '90d' => 'últimos 90 días',
            default => 'últimos 30 días',
        };
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
