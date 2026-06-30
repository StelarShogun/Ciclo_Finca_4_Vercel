<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportsByCategoryRequest;
use App\Http\Requests\Admin\Reports\SalesPerformanceRangeRequest;
use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Services\Admin\ClientPurchaseHistoryQuery;
use App\Services\Admin\Inventory\InventoryMovementQuery;
use App\Services\Admin\Reports\CatalogMostSearchedProductsReportService;
use App\Services\Admin\Reports\CategorySalesReportService;
use App\Services\Admin\Reports\ProductSalesReportService;
use App\Services\Admin\Reports\SalesPerformanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Reportes admin para el SPA Next. Las previsualizaciones (KPIs, tablas,
 * datos de gráfico) salen como JSON de los Services existentes; la generación
 * de PDF/Excel/CSV sigue en Laravel y se descarga desde las rutas web.
 */
final class ReportController extends Controller
{
    public function salesPerformance(SalesPerformanceRangeRequest $request, SalesPerformanceReportService $service): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        return response()->json(['data' => $service->metricsPayload($request->validated())]);
    }

    public function productSales(Request $request, ProductSalesReportService $service): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        $payload = $service->tablePayload($request);
        unset($payload['pagination_html']); // sólo aplica al render Blade del web.

        return response()->json(['data' => $payload]);
    }

    public function categorySales(ReportsByCategoryRequest $request, CategorySalesReportService $service): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        return response()->json(['data' => $service->payload($request, $request->validated())]);
    }

    public function clientPurchases(Request $request, ClientPurchaseHistoryQuery $query): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        $payload = $query->tablePayload([
            'period' => (string) $request->query('period', '30d'),
            'sort' => (string) $request->query('sort', 'total'),
            'dir' => (string) $request->query('dir', 'desc'),
            'q' => (string) $request->query('q', ''),
            'page' => (int) $request->query('page', 1),
        ]);
        unset($payload['pagination_html']);

        return response()->json(['data' => $payload]);
    }

    public function catalogSearch(Request $request, CatalogMostSearchedProductsReportService $service): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        return response()->json(['data' => $service->payload($request->query('period', '30d'))]);
    }

    public function inventoryMovements(Request $request, InventoryMovementQuery $query): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', InventoryMovement::class);

        return response()->json(['data' => $query->indexPayload([
            'search' => (string) $request->query('search', ''),
            'type' => (string) $request->query('type', ''),
            'page' => (int) $request->query('page', 1),
        ])]);
    }
}
