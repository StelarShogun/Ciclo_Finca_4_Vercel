<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\ProductSalesReportQuery;
use App\Services\Admin\ReportPdfFilename;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
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
}
