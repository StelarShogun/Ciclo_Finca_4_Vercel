<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\SalesPerformanceRangeRequest;
use App\Services\Admin\ProductSalesExcelExport;
use App\Services\Admin\Reports\ProductSalesReportService;
use App\Services\Admin\Reports\SalesPerformanceReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
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

    private function authorizeReportView(): void
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('reports.view');
    }

    private function authorizeReportExport(): void
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('reports.export');
    }
}
