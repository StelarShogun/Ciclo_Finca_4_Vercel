<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportsByCategoryRequest;
use App\Http\Requests\Admin\Reports\ReportsIndexRequest;
use App\Http\Requests\Admin\Reports\SalesPerformanceRangeRequest;
use App\Http\Requests\Admin\Reports\SalesPerformanceViewRequest;
use App\Services\Admin\ProductSalesExcelExport;
use App\Services\Admin\Reports\CatalogMostSearchedProductsReportService;
use App\Services\Admin\Reports\CategorySalesReportService;
use App\Services\Admin\Reports\ProductSalesReportService;
use App\Services\Admin\Reports\ReportsExportHubBuilder;
use App\Services\Admin\Reports\SalesPerformanceReportService;
use App\ViewModels\Admin\ReportsIndexViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    // Renders the main reports dashboard.
    public function index(ReportsIndexRequest $request)
    {
        $this->authorizeReportView();

        return Inertia::render('Admin/Reports/Index', ReportsIndexViewModel::from($request->validated()));
    }

    // Renders the centralised export hub.
    // Passes pre-loaded option lists so the modal filters show real names instead of IDs.
    public function exports(Request $request, ReportsExportHubBuilder $hub)
    {
        $this->authorizeReportExport();

        return Inertia::render('Admin/Reports/Exports', [
            'exportsConfig' => $hub->build($request),
        ]);
    }

    // Renders the sales-performance view (CF4-24).
    // Metrics are loaded asynchronously via JSON; only the initial filter state is passed here.
    public function salesPerformance(SalesPerformanceViewRequest $request)
    {
        $this->authorizeReportView();

        return Inertia::render('Admin/Reports/SalesPerformance', $request->initialState());
    }

    // Returns the resolved current and previous date ranges for the selected preset as JSON (CF4-24).
    public function salesPerformanceRange(SalesPerformanceRangeRequest $request, SalesPerformanceReportService $salesPerformance)
    {
        $this->authorizeReportView();

        return response()->json($salesPerformance->rangePayload($request->validated()));
    }

    // Returns aggregated completed-sales metrics for the current and previous periods, including a comparison delta (CF4-24).
    public function salesPerformanceMetrics(
        SalesPerformanceRangeRequest $request,
        SalesPerformanceReportService $salesPerformance,
    ) {
        $this->authorizeReportView();

        return response()->json($salesPerformance->metricsPayload($request->validated()));
    }

    // Renders the product-sales report view with normalised filter state.
    public function productSales(Request $request, ProductSalesReportService $productSales)
    {
        $this->authorizeReportView();

        return Inertia::render('Admin/Reports/ProductSales', $productSales->initialPayload($request));
    }

    /** CF4-108 — productos más vistos en resultados de búsqueda del catálogo público. */
    public function catalogMostSearchedProducts(Request $request, CatalogMostSearchedProductsReportService $catalogSearch)
    {
        $this->authorizeReportView();

        return Inertia::render('Admin/Reports/CatalogSearch', $catalogSearch->payload($request->query('period')));
    }

    // Returns a paginated product-sales table plus the Top 10 chart data as JSON.
    public function productSalesTable(Request $request, ProductSalesReportService $productSales)
    {
        $this->authorizeReportView();

        return response()->json($productSales->tablePayload($request));
    }

    // Generates and streams a PDF export of the product-sales report, including the Top 10 chart and the full table.
    public function productSalesPdf(Request $request, ProductSalesReportService $productSales)
    {
        $this->authorizeReportExport();

        return $productSales->pdf($request);
    }

    // Generates and streams an Excel export of the product-sales report.
    // Applies the same dataset and filter logic as the PDF export.
    // Output filename format: reporte-productos-vendidos-YYYY-MM-DD.xlsx
    public function productSalesExcel(
        Request $request,
        ProductSalesExcelExport $excelExport,
        ProductSalesReportService $productSales,
    ): StreamedResponse {
        $this->authorizeReportExport();

        return $productSales->excel($request, $excelExport);
    }

    public function byCategory(ReportsByCategoryRequest $request, CategorySalesReportService $categorySales)
    {
        $this->authorizeReportView();

        return Inertia::render('Admin/Reports/ByCategory', $categorySales->payload($request, $request->validated()));
    }

    private function authorizeReportView(): void
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('reports.view');
    }

    private function authorizeReportExport(): void
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('reports.export');
    }
}
