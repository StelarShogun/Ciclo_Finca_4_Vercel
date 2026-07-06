<?php

namespace App\Services\Admin\Reports;

use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\AdminPdfExportService;
use App\Services\Admin\ProductSalesExcelExport;
use App\Services\Admin\ProductSalesReportQuery;
use App\Services\Admin\ReportExcelFilename;
use App\Support\AdminPerPage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductSalesReportService
{
    private const PERIODS = ['7d', '30d', '90d', 'custom'];

    private const TOP10_METRICS = ['revenue', 'units'];

    public function initialPayload(Request $request): array
    {
        return [
            'period' => $this->normalizePeriod($request->query('period')),
            'sort' => $this->normalizeSort($request->query('sort')),
            'dir' => $this->normalizeDir($request->query('dir')),
            'q' => $this->normalizeQuery($request->query('q')),
            'top10' => $this->normalizeTop10Metric($request->query('top10')),
        ];
    }

    public function tablePayload(Request $request): array
    {
        $period = $this->normalizePeriod($request->query('period'));
        $sort = $this->normalizeSort($request->query('sort'));
        $dir = $this->normalizeDir($request->query('dir'));
        $q = $this->normalizeQuery($request->query('q'));
        $top10Metric = $this->normalizeTop10Metric($request->query('top10'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = AdminPerPage::resolve($request->query('per_page', 10));

        [$start, $end] = $this->range($request, $period);
        $top10SortColumn = $top10Metric === 'units' ? 'units_sold' : 'revenue';
        $sortColumn = $sort === 'units' ? 'units_sold' : 'revenue';

        $top10 = ProductSalesReportQuery::base($start, $q, $end)
            ->orderByDesc($top10SortColumn)
            ->limit(10)
            ->get();

        $paginator = ProductSalesReportQuery::base($start, $q, $end)
            ->orderBy($sortColumn, $dir)
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->setPath('/api/v1/admin/reports/product-sales');
        $paginator->appends([
            'period' => $period,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q !== '' ? $q : null,
            'top10' => $top10Metric,
            'per_page' => $perPage,
        ]);

        $formatRow = fn ($row) => ProductSalesReportQuery::formatRow($row);

        return [
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
            'pagination_html' => view('components.admin.pagination', [
                'paginator' => $paginator,
                'label' => 'reporte',
                'perPageSubmit' => false,
            ])->render(),
        ];
    }

    public function pdf(Request $request): Response
    {
        $period = $this->normalizePeriod($request->query('period'));
        $sort = $this->normalizeSort($request->query('sort'));
        $dir = $this->normalizeDir($request->query('dir'));
        $q = $this->normalizeQuery($request->query('q'));
        $top10Metric = $this->normalizeTop10Metric($request->query('top10'));

        [$start, $end] = $this->range($request, $period);
        $top10SortColumn = $top10Metric === 'units' ? 'units_sold' : 'revenue';
        $sortColumn = $sort === 'units' ? 'units_sold' : 'revenue';

        $top10 = ProductSalesReportQuery::base($start, $q, $end)
            ->orderByDesc($top10SortColumn)
            ->limit(10)
            ->get();

        $maxRows = AdminPdfExportLimits::PRODUCT_SALES_TABLE_MAX_ROWS;
        $tableRows = ProductSalesReportQuery::base($start, $q, $end)
            ->orderBy($sortColumn, $dir)
            ->limit($maxRows)
            ->get();

        $totalMatching = (int) DB::query()
            ->fromSub(ProductSalesReportQuery::base($start, $q, $end), 'product_sales_agg')
            ->count();

        $filterLines = $this->filterLines($period, $top10Metric, $sort, $dir, $q, $totalMatching, $maxRows);
        $top10Formatted = $top10->map(fn ($row) => ProductSalesReportQuery::formatRow($row));
        $tableFormatted = $tableRows->map(fn ($row) => ProductSalesReportQuery::formatRow($row));

        $logoPath = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        return app(AdminPdfExportService::class)->download('admin.reports.product-sales-pdf', [
            'period' => $period,
            'top10Metric' => $top10Metric,
            'top10' => $top10Formatted,
            'tableRows' => $tableFormatted,
            'maxBarUnits' => max(1, (int) $top10Formatted->max('units_sold')),
            'maxBarRevenue' => max(1.0, (float) $top10Formatted->max('revenue')),
            'pdfTitle' => 'Productos más vendidos',
            'pdfSubtitle' => 'Ventas completadas — Ciclo Finca 4',
            'logoPath' => is_file($logoPath) ? $logoPath : null,
            'filterLines' => $filterLines,
            'generatedFor' => 'Administración',
        ], 'productos-vendidos');
    }

    public function excel(Request $request, ProductSalesExcelExport $excelExport): StreamedResponse
    {
        $period = $this->normalizePeriod($request->query('period'));
        $sort = $this->normalizeSort($request->query('sort'));
        $dir = $this->normalizeDir($request->query('dir'));
        $q = $this->normalizeQuery($request->query('q'));
        $top10Metric = $this->normalizeTop10Metric($request->query('top10'));

        [$start, $end] = $this->range($request, $period);
        $top10SortColumn = $top10Metric === 'units' ? 'units_sold' : 'revenue';
        $sortColumn = $sort === 'units' ? 'units_sold' : 'revenue';
        $maxRows = AdminPdfExportLimits::PRODUCT_SALES_TABLE_MAX_ROWS;

        $top10 = ProductSalesReportQuery::base($start, $q, $end)
            ->orderByDesc($top10SortColumn)
            ->limit(10)
            ->get()
            ->map(fn ($row) => ProductSalesReportQuery::formatRow($row));

        $tableRows = ProductSalesReportQuery::base($start, $q, $end)
            ->orderBy($sortColumn, $dir)
            ->limit($maxRows)
            ->get()
            ->map(fn ($row) => ProductSalesReportQuery::formatRow($row));

        $totalMatching = (int) DB::query()
            ->fromSub(ProductSalesReportQuery::base($start, $q, $end), 'product_sales_agg')
            ->count();

        return $excelExport->download(
            $top10,
            $tableRows,
            $top10Metric,
            $this->filterLines($period, $top10Metric, $sort, $dir, $q, $totalMatching, $maxRows),
            ReportExcelFilename::make('productos-vendidos'),
        );
    }

    private function filterLines(string $period, string $top10Metric, string $sort, string $dir, string $q, int $totalMatching, int $maxRows): array
    {
        $lines = [
            'Periodo: '.$this->periodLabel($period),
            'Top 10 por: '.($top10Metric === 'units' ? 'unidades' : 'ingresos'),
            'Tabla ordenada por: '.($sort === 'units' ? 'unidades' : 'ingresos').' ('.$dir.')',
        ];

        if ($q !== '') {
            $lines[] = 'Búsqueda: '.$q;
        }

        if ($totalMatching > $maxRows) {
            $lines[] = 'Nota: la exportación incluye como máximo '.$maxRows.' filas ('.$totalMatching.' productos con ventas en el periodo).';
        }

        return $lines;
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            '7d' => 'últimos 7 días',
            '90d' => 'últimos 90 días',
            default => 'últimos 30 días',
        };
    }

    private function range(Request $request, string $period): array
    {
        if ($period !== 'custom') {
            return [$this->periodStart($period), null];
        }

        $from = $this->parseDate($request->query('date_from'));
        $to = $this->parseDate($request->query('date_to'));
        $start = $from ? $from->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
        $end = $to ? $to->endOfDay() : Carbon::now()->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            '7d' => Carbon::now()->subDays(6)->startOfDay(),
            '90d' => Carbon::now()->subDays(89)->startOfDay(),
            default => Carbon::now()->subDays(29)->startOfDay(),
        };
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($value)) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizePeriod(mixed $value): string
    {
        $value = is_string($value) ? $value : '';

        return in_array($value, self::PERIODS, true) ? $value : '30d';
    }

    private function normalizeSort(mixed $value): string
    {
        return is_string($value) && $value === 'units' ? 'units' : 'revenue';
    }

    private function normalizeDir(mixed $value): string
    {
        return is_string($value) && strtolower($value) === 'asc' ? 'asc' : 'desc';
    }

    private function normalizeQuery(mixed $value): string
    {
        return is_string($value) ? mb_substr(trim($value), 0, 100) : '';
    }

    private function normalizeTop10Metric(mixed $value): string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($value, self::TOP10_METRICS, true) ? $value : 'revenue';
    }
}
