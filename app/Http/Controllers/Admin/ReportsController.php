<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\SalesPerformanceRangeRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\AdminPdfExportService;
use App\Services\Admin\CatalogMostSearchedProductsReportQuery;
use App\Services\Admin\ProductSalesExcelExport;
use App\Services\Admin\ProductSalesReportQuery;
use App\Services\Admin\ReportExcelFilename;
use App\Services\SalesPerformanceDateRangeService;
use App\Services\SalesPerformanceMetricsService;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    // Allowed relative period slugs for product-sales reports.
    private const PERIODS = ['7d', '30d', '90d'];

    // Allowed sort dimensions for the Top 10 chart.
    private const TOP10_METRICS = ['revenue', 'units'];

    // Renders the main reports dashboard.
    public function index()
    {
        return Inertia::render('Admin/Reports/Index');
    }

    // Renders the centralised export hub.
    // Passes pre-loaded option lists so the modal filters show real names instead of IDs.
    public function exports()
    {
        // Categorías raíz (canónicas) para el filtro de inventario.
        $parentCategories = Category::whereNull('parent_category_id')
            ->orderBy('name')
            ->get(['category_id', 'name'])
            ->unique(fn ($c) => mb_strtolower(trim($c->name)))
            ->values();

        // Subcategorías agrupadas por id canónico de categoría padre.
        $subcatsByParent = Category::subcategoriesGroupedByCanonicalParent();

        // Proveedores con sus datos de contacto para autorrelleno.
        $suppliers = Supplier::orderBy('name')
            ->get(['supplier_id', 'name', 'primary_contact', 'phone', 'email']);

        // Marcas para el filtro de proveedores de marcas (si aplica en el futuro).
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        $parentCatOptions = array_merge(
            [['value' => '', 'label' => 'Todas']],
            $parentCategories->map(fn ($c) => ['value' => (string) $c->category_id, 'label' => $c->name])->all(),
        );

        $subcatOptionsMap = [];
        foreach ($subcatsByParent as $parentId => $subcats) {
            $subcatOptionsMap[(string) $parentId] = array_merge(
                [['value' => '', 'label' => 'Todas']],
                collect($subcats)->map(fn ($s) => ['value' => (string) $s['category_id'], 'label' => $s['name']])->all(),
            );
        }

        $supplierOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            $suppliers->map(fn ($s) => ['value' => $s->name, 'label' => $s->name])->all(),
        );

        $supplierAutofillData = [];
        foreach ($suppliers as $s) {
            $supplierAutofillData[$s->name] = ['contact' => $s->primary_contact ?? ''];
        }

        $orderStateOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            collect(\App\Models\Order::STATE_LABELS)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all(),
        );

        $brandOptions = array_merge(
            [['value' => '', 'label' => 'Todas']],
            $brands->map(fn ($b) => ['value' => $b->name, 'label' => $b->name])->all(),
        );

        $exportsConfig = [
            'exports' => [
                'dashboard' => [
                    'id' => 'dashboard', 'title' => 'Dashboard',
                    'subtitle' => 'Exporta el PDF del dashboard para un periodo.',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('dashboard.export')],
                    'filters' => [
                        ['name' => 'period', 'label' => 'Periodo', 'type' => 'select', 'default' => '30d', 'options' => [
                            ['value' => '7d', 'label' => '7 días'], ['value' => '30d', 'label' => '30 días'], ['value' => '90d', 'label' => '90 días'],
                        ]],
                    ],
                    'staticParams' => [],
                ],
                'inventory' => [
                    'id' => 'inventory', 'title' => 'Inventario',
                    'subtitle' => 'Exporta inventario en paquete ZIP (con imágenes), JSON, PDF, Excel o XML.',
                    'formatMode' => 'path',
                    'baseUrls' => [
                        'bundle' => route('products.export', ['format' => 'bundle']),
                        'json' => route('products.export', ['format' => 'json']),
                        'pdf' => route('products.export', ['format' => 'pdf']),
                        'excel' => route('products.export', ['format' => 'excel']),
                        'xml' => route('products.export', ['format' => 'xml']),
                    ],
                    'initialValues' => request()->only(\App\Services\Admin\AdminInventoryExportQuery::QUERY_KEYS),
                    'filters' => [
                        ['name' => 'search', 'label' => 'Búsqueda', 'type' => 'text', 'placeholder' => 'Nombre o descripción'],
                        ['name' => 'parent_category_id', 'label' => 'Categoría', 'type' => 'select', 'options' => $parentCatOptions, 'cascades' => 'subcategory_id', 'cascadeOptions' => $subcatOptionsMap],
                        ['name' => 'subcategory_id', 'label' => 'Subcategoría', 'type' => 'select', 'options' => [['value' => '', 'label' => 'Todas']]],
                        ['name' => 'stock_status', 'label' => 'Stock', 'type' => 'select', 'options' => [
                            ['value' => '', 'label' => 'Todos'], ['value' => 'in-stock', 'label' => 'En stock'], ['value' => 'low', 'label' => 'Stock bajo'], ['value' => 'out', 'label' => 'Agotado'],
                        ]],
                        ['name' => 'status', 'label' => 'Estado', 'type' => 'select', 'options' => [
                            ['value' => '', 'label' => 'Todos'], ['value' => 'active', 'label' => 'Activo'], ['value' => 'inactive', 'label' => 'Inactivo'],
                        ]],
                    ],
                ],
                'sales' => [
                    'id' => 'sales', 'title' => 'Ventas', 'subtitle' => 'Exporta ventas en PDF o Excel.',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('sales.export'), 'excel' => route('sales.export')],
                    'staticParams' => [],
                    'initialValues' => array_merge(
                        ['status' => 'completed', 'date_range' => 'month'],
                        request()->only(['status', 'date_range', 'date_from', 'date_to', 'payment_method', 'search']),
                    ),
                    'filters' => [
                        ['name' => 'status', 'label' => 'Estado', 'type' => 'select', 'options' => [
                            ['value' => 'completed', 'label' => 'Confirmadas'], ['value' => 'cancelled', 'label' => 'Canceladas'], ['value' => 'refunded', 'label' => 'Reembolsadas'], ['value' => 'all', 'label' => 'Todas'],
                        ]],
                        ['name' => 'date_range', 'label' => 'Rango de fecha', 'type' => 'select', 'options' => [
                            ['value' => 'today', 'label' => 'Hoy'], ['value' => 'week', 'label' => 'Esta semana'], ['value' => 'month', 'label' => 'Este mes'], ['value' => 'custom', 'label' => 'Por fechas'],
                        ]],
                        ['name' => 'date_from', 'label' => 'Desde', 'type' => 'date'],
                        ['name' => 'date_to', 'label' => 'Hasta', 'type' => 'date'],
                        ['name' => 'payment_method', 'label' => 'Método de pago', 'type' => 'text', 'placeholder' => 'efectivo, tarjeta, ...'],
                        ['name' => 'search', 'label' => 'Buscar', 'type' => 'text', 'placeholder' => 'Factura, cliente, ...'],
                    ],
                ],
                'productSales' => [
                    'id' => 'productSales', 'title' => 'Productos más vendidos',
                    'subtitle' => 'Exporta el reporte de productos más vendidos.',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('admin.reports.product-sales.pdf'), 'excel' => route('admin.reports.product-sales.excel')],
                    'initialValues' => array_merge(
                        ['period' => '30d', 'sort' => 'revenue', 'dir' => 'desc', 'top10' => 'revenue'],
                        request()->only(['period', 'sort', 'dir', 'q', 'top10']),
                    ),
                    'filters' => [
                        ['name' => 'period', 'label' => 'Periodo', 'type' => 'select', 'options' => [
                            ['value' => '7d', 'label' => '7 días'], ['value' => '30d', 'label' => '30 días'], ['value' => '90d', 'label' => '90 días'],
                        ]],
                        ['name' => 'sort', 'label' => 'Ordenar por', 'type' => 'select', 'options' => [
                            ['value' => 'revenue', 'label' => 'Ingresos'], ['value' => 'units', 'label' => 'Unidades'],
                        ]],
                        ['name' => 'dir', 'label' => 'Dirección', 'type' => 'select', 'options' => [
                            ['value' => 'desc', 'label' => 'Descendente'], ['value' => 'asc', 'label' => 'Ascendente'],
                        ]],
                        ['name' => 'q', 'label' => 'Buscar', 'type' => 'text', 'placeholder' => 'Nombre o código'],
                        ['name' => 'top10', 'label' => 'Top 10 por', 'type' => 'select', 'options' => [
                            ['value' => 'revenue', 'label' => 'Ingresos'], ['value' => 'units', 'label' => 'Unidades'],
                        ]],
                    ],
                ],
                'registry.suppliers' => [
                    'id' => 'registry.suppliers', 'title' => 'Proveedores', 'subtitle' => 'Listado administrativo de proveedores.',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('admin.reports.exports.registry', ['slug' => 'proveedores']), 'excel' => route('admin.reports.exports.registry', ['slug' => 'proveedores'])],
                    'initialValues' => request()->only(\App\Services\Admin\AdminSuppliersCatalogExportQuery::QUERY_KEYS),
                    'filters' => [
                        ['name' => 'name', 'label' => 'Proveedor', 'type' => 'select', 'options' => $supplierOptions, 'autofills' => ['contact'], 'autofillData' => $supplierAutofillData],
                        ['name' => 'contact', 'label' => 'Contacto', 'type' => 'text', 'placeholder' => 'Se completa al elegir proveedor', 'readonly' => true],
                    ],
                ],
                'registry.brands' => [
                    'id' => 'registry.brands', 'title' => 'Marcas', 'subtitle' => 'Listado administrativo de marcas.',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('admin.reports.exports.registry', ['slug' => 'marcas']), 'excel' => route('admin.reports.exports.registry', ['slug' => 'marcas'])],
                    'initialValues' => request()->only(\App\Services\Admin\AdminBrandsCatalogExportQuery::QUERY_KEYS),
                    'filters' => [
                        ['name' => 'name', 'label' => 'Marca', 'type' => 'select', 'options' => $brandOptions],
                    ],
                ],
                'registry.supplierOrders' => [
                    'id' => 'registry.supplierOrders', 'title' => 'Pedidos a proveedores', 'subtitle' => 'Listado de pedidos a proveedores.',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('admin.reports.exports.registry', ['slug' => 'pedidos-proveedores']), 'excel' => route('admin.reports.exports.registry', ['slug' => 'pedidos-proveedores'])],
                    'initialValues' => request()->only(\App\Services\Admin\AdminSupplierOrdersExportQuery::QUERY_KEYS),
                    'filters' => [
                        ['name' => 'state', 'label' => 'Estado', 'type' => 'select', 'options' => $orderStateOptions],
                        ['name' => 'search', 'label' => 'Buscar', 'type' => 'text', 'placeholder' => 'Proveedor, número de pedido, ...'],
                        ['name' => 'date_from', 'label' => 'Desde', 'type' => 'date'],
                        ['name' => 'date_to', 'label' => 'Hasta', 'type' => 'date'],
                    ],
                ],
                'registry.users' => [
                    'id' => 'registry.users', 'title' => 'Usuarios', 'subtitle' => 'Listado de usuarios/clientes.',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('admin.reports.exports.registry', ['slug' => 'usuarios']), 'excel' => route('admin.reports.exports.registry', ['slug' => 'usuarios'])],
                    'initialValues' => [], 'filters' => [],
                ],
                'registry.clientOrders' => [
                    'id' => 'registry.clientOrders', 'title' => 'Encargos', 'subtitle' => 'Listado de pedidos de clientes (encargos).',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('admin.reports.exports.registry', ['slug' => 'pedidos-clientes']), 'excel' => route('admin.reports.exports.registry', ['slug' => 'pedidos-clientes'])],
                    'initialValues' => request()->only(\App\Services\Admin\AdminClientOrdersExportQuery::QUERY_KEYS),
                    'filters' => [
                        ['name' => 'status', 'label' => 'Estado', 'type' => 'select', 'options' => [
                            ['value' => '', 'label' => 'Todos'], ['value' => 'pending', 'label' => 'Pendiente'], ['value' => 'confirmed', 'label' => 'Confirmado'], ['value' => 'completed', 'label' => 'Completado'], ['value' => 'cancelled', 'label' => 'Cancelado'],
                        ]],
                        ['name' => 'search', 'label' => 'Buscar', 'type' => 'text', 'placeholder' => 'Nombre, correo, ...'],
                    ],
                ],
            ],
        ];

        return Inertia::render('Admin/Reports/Exports', [
            'exportsConfig' => $exportsConfig,
        ]);
    }

    // Renders the sales-performance view (CF4-24).
    // Metrics are loaded asynchronously via JSON; only the initial filter state is passed here.
    public function salesPerformance(Request $request)
    {
        $allowed = ['today', 'week', 'month', 'year', 'custom'];
        $preset = (string) $request->query('preset', 'month');
        // Fall back to 'month' for any unrecognised preset value.
        if (! in_array($preset, $allowed, true)) {
            $preset = 'month';
        }
        $from = $request->query('from');
        $to = $request->query('to');

        return Inertia::render('Admin/Reports/SalesPerformance', [
            'initialPreset' => $preset,
            'initialFrom' => is_string($from) ? $from : '',
            'initialTo' => is_string($to) ? $to : '',
        ]);
    }

    // Returns the resolved current and previous date ranges for the selected preset as JSON (CF4-24).
    public function salesPerformanceRange(SalesPerformanceRangeRequest $request, SalesPerformanceDateRangeService $rangeService)
    {
        $resolved = $rangeService->resolve($request->validated());

        return response()->json($this->salesPerformanceRangePayload($resolved));
    }

    // Returns aggregated completed-sales metrics for the current and previous periods, including a comparison delta (CF4-24).
    public function salesPerformanceMetrics(
        SalesPerformanceRangeRequest $request,
        SalesPerformanceDateRangeService $rangeService,
        SalesPerformanceMetricsService $metricsService,
    ) {
        $resolved = $rangeService->resolve($request->validated());
        $current = $metricsService->aggregateCompletedSales(
            $resolved['current_start']->utc(),
            $resolved['current_end']->utc(),
        );
        $previous = $metricsService->aggregateCompletedSales(
            $resolved['previous_start']->utc(),
            $resolved['previous_end']->utc(),
        );
        $comparison = $metricsService->comparisonVersusPrior($current, $previous);

        // Merge period metadata with the computed metrics before returning.
        return response()->json(array_merge($this->salesPerformanceRangePayload($resolved), [
            'current_metrics' => $current,
            'previous_metrics' => $previous,
            'comparison' => $comparison,
        ]));
    }

    // Builds the shared period metadata payload included in all sales-performance JSON responses.
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

    // Renders the product-sales report view with normalised filter state.
    public function productSales(Request $request)
    {
        return Inertia::render('Admin/Reports/ProductSales', [
            'period' => $this->normalizePeriod($request->query('period')),
            'sort' => $this->normalizeSort($request->query('sort')),
            'dir' => $this->normalizeDir($request->query('dir')),
            'q' => $this->normalizeQuery($request->query('q')),
            'top10' => $this->normalizeTop10Metric($request->query('top10')),
        ]);
    }

    /** CF4-108 — productos más vistos en resultados de búsqueda del catálogo público. */
    public function catalogMostSearchedProducts(Request $request)
    {
        $period = $this->normalizeCatalogSearchPeriod($request->query('period'));
        $rows = CatalogMostSearchedProductsReportQuery::topProducts($period, 50);
        $totalEvents = CatalogMostSearchedProductsReportQuery::totalEventsSince($period);
        $uniqueProducts = CatalogMostSearchedProductsReportQuery::distinctProductCount($period);
        $topRow = $rows->first();
        $maxHits = max(1, (int) ($rows->max('hit_count') ?? 0));

        return Inertia::render('Admin/Reports/CatalogSearch', [
            'period' => $period,
            'rows' => $rows->map(fn ($row): array => [
                'product_id' => (int) $row->product_id,
                'name' => (string) $row->name,
                'sku' => \App\Models\Product::skuFromId((int) $row->product_id),
                'hit_count' => (int) $row->hit_count,
            ])->values()->all(),
            'totalEvents' => (int) $totalEvents,
            'uniqueProducts' => (int) $uniqueProducts,
            'topProductName' => $topRow->name ?? null,
            'topProductHits' => isset($topRow->hit_count) ? (int) $topRow->hit_count : null,
            'maxHits' => (int) $maxHits,
        ]);
    }

    /** @return '7d'|'30d'|'90d' */
    private function normalizeCatalogSearchPeriod(mixed $value): string
    {
        $v = is_string($value) ? $value : '';

        return in_array($v, ['7d', '30d', '90d'], true) ? $v : '30d';
    }

    // Returns a paginated product-sales table plus the Top 10 chart data as JSON.
    public function productSalesTable(Request $request)
    {
        $period = $this->normalizePeriod($request->query('period'));
        $sort = $this->normalizeSort($request->query('sort'));
        $dir = $this->normalizeDir($request->query('dir'));
        $q = $this->normalizeQuery($request->query('q'));
        $top10Metric = $this->normalizeTop10Metric($request->query('top10'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = AdminPerPage::resolve($request->query('per_page', 10));

        $start = $this->periodStart($period);

        // Resolve the sort column for the Top 10 chart independently of the table sort.
        $top10SortColumn = $top10Metric === 'units' ? 'units_sold' : 'revenue';
        $top10 = ProductSalesReportQuery::base($start, $q)
            ->orderByDesc($top10SortColumn)
            ->limit(10)
            ->get();

        $sortColumn = $sort === 'units' ? 'units_sold' : 'revenue';
        $paginator = ProductSalesReportQuery::base($start, $q)
            ->orderBy($sortColumn, $dir)
            ->paginate($perPage, ['*'], 'page', $page);

        // Attach stable URLs with the current filter state to all paginator links.
        $paginator->setPath(route('admin.reports.product-sales.table'));
        $paginator->appends([
            'period' => $period,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q !== '' ? $q : null,
            'top10' => $top10Metric,
            'per_page' => $perPage,
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
            // Pre-rendered pagination HTML consumed directly by the frontend.
            'pagination_html' => view('components.admin.pagination', [
                'paginator' => $paginator,
                'label' => 'reporte',
                'perPageSubmit' => false,
            ])->render(),
        ]);
    }

    // Generates and streams a PDF export of the product-sales report, including the Top 10 chart and the full table.
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

        // Count the full result set to determine whether the row cap was reached.
        $totalMatching = (int) DB::query()
            ->fromSub(ProductSalesReportQuery::base($start, $q), 'product_sales_agg')
            ->count();

        $filterLines = $this->buildProductSalesFilterLines(
            $period, $top10Metric, $sort, $dir, $q, $totalMatching, $maxRows
        );

        $top10Formatted = $top10->map(fn ($row) => ProductSalesReportQuery::formatRow($row));
        $tableFormatted = $tableRows->map(fn ($row) => ProductSalesReportQuery::formatRow($row));

        // Derive the chart scale from the Top 10 dataset; guard against empty or zero values.
        $maxBarUnits = max(1, (int) $top10Formatted->max('units_sold'));
        $maxBarRevenue = max(1.0, (float) $top10Formatted->max('revenue'));

        $logoPath = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        return app(AdminPdfExportService::class)->download('admin.reports.product-sales-pdf', [
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
        ], 'productos-vendidos');
    }

    // Generates and streams an Excel export of the product-sales report.
    // Applies the same dataset and filter logic as the PDF export.
    // Output filename format: reporte-productos-vendidos-YYYY-MM-DD.xlsx
    public function productSalesExcel(Request $request, ProductSalesExcelExport $excelExport): StreamedResponse
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
            ->get()
            ->map(fn ($row) => ProductSalesReportQuery::formatRow($row));

        $maxRows = AdminPdfExportLimits::PRODUCT_SALES_TABLE_MAX_ROWS;
        $tableRows = ProductSalesReportQuery::base($start, $q)
            ->orderBy($sortColumn, $dir)
            ->limit($maxRows)
            ->get()
            ->map(fn ($row) => ProductSalesReportQuery::formatRow($row));

        // Count the full result set to determine whether the row cap was reached.
        $totalMatching = (int) DB::query()
            ->fromSub(ProductSalesReportQuery::base($start, $q), 'product_sales_agg')
            ->count();

        $filterLines = $this->buildProductSalesFilterLines(
            $period, $top10Metric, $sort, $dir, $q, $totalMatching, $maxRows
        );

        return $excelExport->download(
            $top10,
            $tableRows,
            $top10Metric,
            $filterLines,
            ReportExcelFilename::make('productos-vendidos'),
        );
    }

    // ── Shared filter-line builder ────────────────────────────────────────────

    // Builds the list of human-readable filter descriptions displayed in the PDF and Excel document headers.
    // Appends a row-cap notice when the result set exceeds the export limit.
    private function buildProductSalesFilterLines(
        string $period,
        string $top10Metric,
        string $sort,
        string $dir,
        string $q,
        int $totalMatching,
        int $maxRows,
    ): array {
        $lines = [
            'Periodo: '.$this->periodLabel($period),
            'Top 10 por: '.($top10Metric === 'units' ? 'unidades' : 'ingresos'),
            'Tabla ordenada por: '.($sort === 'units' ? 'unidades' : 'ingresos').' ('.$dir.')',
        ];

        if ($q !== '') {
            $lines[] = 'Búsqueda: '.$q;
        }

        // Warn the user when the dataset was truncated to fit within the export row limit.
        if ($totalMatching > $maxRows) {
            $lines[] = 'Nota: la exportación incluye como máximo '.$maxRows.' filas ('.$totalMatching.' productos con ventas en el periodo).';
        }

        return $lines;
    }

    // Returns a localised human-readable label for the given period slug.
    private function periodLabel(string $period): string
    {
        return match ($period) {
            '7d' => 'últimos 7 días',
            '90d' => 'últimos 90 días',
            default => 'últimos 30 días',
        };
    }

    // Returns the start-of-day Carbon timestamp corresponding to the given period slug.
    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            '7d' => Carbon::now()->subDays(6)->startOfDay(),
            '90d' => Carbon::now()->subDays(89)->startOfDay(),
            default => Carbon::now()->subDays(29)->startOfDay(),
        };
    }

    // Returns the period slug if it is in the allowed list; defaults to '30d'.
    private function normalizePeriod(mixed $value): string
    {
        $v = is_string($value) ? $value : '';

        return in_array($v, self::PERIODS, true) ? $v : '30d';
    }

    // Returns 'units' or defaults to 'revenue' for the sort dimension.
    private function normalizeSort(mixed $value): string
    {
        $v = is_string($value) ? $value : '';

        return $v === 'units' ? 'units' : 'revenue';
    }

    // Returns 'asc' or defaults to 'desc' for the sort direction.
    private function normalizeDir(mixed $value): string
    {
        $v = is_string($value) ? strtolower($value) : '';

        return $v === 'asc' ? 'asc' : 'desc';
    }

    // Trims and truncates the search query to a maximum of 100 characters.
    private function normalizeQuery(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }
        $t = trim($value);

        return mb_substr($t, 0, 100);
    }

    // Returns the Top 10 metric if it is in the allowed list; defaults to 'revenue'.
    private function normalizeTop10Metric(mixed $value): string
    {
        $v = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($v, self::TOP10_METRICS, true) ? $v : 'revenue';
    }

    // Formats a date range as a localised 'dd/mm/YYYY - dd/mm/YYYY' string for display.
    private function humanRangeLabel(CarbonInterface $start, CarbonInterface $end): string
    {
        return $start->format('d/m/Y').' - '.$end->format('d/m/Y');
    }

    public function byCategory(Request $request)
    {
        $dateRange = $request->input('date_range', 'month');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateRange === 'custom') {
            $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
            ], [
                'date_from.required' => 'La fecha de inicio es obligatoria.',
                'date_from.date' => 'La fecha de inicio no es válida.',
                'date_to.required' => 'La fecha de fin es obligatoria.',
                'date_to.date' => 'La fecha de fin no es válida.',
                'date_to.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            ]);
        }

        [$from, $to] = $this->resolveDateRange($dateRange, $dateFrom, $dateTo);

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

        $chartData = $rows->map(function ($r) {
            return [
                'label' => $r->category_name,
                'value' => $r->total_revenue,
                'percent' => $r->percentage,
            ];
        })->values()->toArray();

        return Inertia::render('Admin/Reports/ByCategory', [
            'rows' => $rows->map(fn ($r): array => [
                'category_id' => (int) $r->category_id,
                'category_name' => (string) $r->category_name,
                'total_units' => (int) $r->total_units,
                'total_revenue' => (float) $r->total_revenue,
                'percentage' => (float) $r->percentage,
            ])->values()->all(),
            'grandTotal' => (float) $grandTotal,
            'totalUnits' => (int) $rows->sum('total_units'),
            'chartData' => $chartData,
            'filters' => [
                'date_range' => (string) $dateRange,
                'date_from' => (string) ($request->input('date_from') ?? ''),
                'date_to' => (string) ($request->input('date_to') ?? ''),
            ],
        ]);
    }

    private function resolveDateRange(string $range, ?string $dateFrom, ?string $dateTo): array
    {
        return AdminDateRange::boundsAsDateTimeStrings($range, $dateFrom, $dateTo, storedAsUtc: true);
    }
}
