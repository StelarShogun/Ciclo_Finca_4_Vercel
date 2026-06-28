<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\Sales\CancelAdminSale;
use App\Actions\Admin\Sales\CompleteAdminSale;
use App\Actions\Admin\Sales\CreateAdminSale;
use App\Actions\Admin\Sales\DeletePendingAdminSale;
use App\Actions\Admin\Sales\MarkSaleReadyToPickup;
use App\Actions\Admin\Sales\ReturnAdminSale;
use App\Actions\Admin\Sales\UpdateAdminSale;
use App\DTOs\Admin\Sales\AdminSaleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Sales\AdminSalesIndexRequest;
use App\Http\Requests\Admin\Sales\CancelAdminSaleRequest;
use App\Http\Requests\Admin\Sales\ReturnAdminSaleRequest;
use App\Http\Requests\Admin\Sales\StoreAdminSaleRequest;
use App\Http\Requests\Admin\Sales\UpdateAdminSaleRequest;
use App\Http\Resources\Admin\SaleResource;
use App\Models\Sale;
use App\Services\Admin\Sales\AdminSalesQuery;
use App\ViewModels\Admin\SalesIndexViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Ventas admin para el SPA Next. Reusa las Actions (que ya devuelven JSON con
 * transacciones/locks de stock) y AdminSalesQuery. Igual que el web Inertia: el
 * detalle y el ciclo de vida (listo/confirmar/cancelar/devolver/eliminar) van
 * por aquí; factura/impresión/exportación se sirven en Blade/descarga desde el
 * backend (rutas web existentes).
 */
final class SaleController extends Controller
{
    public function index(AdminSalesIndexRequest $request, AdminSalesQuery $salesQuery): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        return response()->json(['data' => SalesIndexViewModel::from($salesQuery->indexPayload($request))]);
    }

    public function heartbeat(Request $request, AdminSalesQuery $salesQuery): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        return response()->json($salesQuery->heartbeatPayload((int) $request->query('since', 0)));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $sale = Sale::with(['client', 'sellerAdmin', 'saleItems.product'])->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $sale);

        return response()->json(['success' => true, 'sale' => SaleResource::make($sale)->resolve($request)]);
    }

    public function store(StoreAdminSaleRequest $request, CreateAdminSale $action): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Sale::class);

        return $action->handle(AdminSaleData::fromArray($request->validated())->toArray());
    }

    public function update(UpdateAdminSaleRequest $request, int $id, UpdateAdminSale $action): JsonResponse
    {
        $sale = Sale::findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $sale);

        return $action->handle($sale, $request->validated());
    }

    public function destroy(CancelAdminSaleRequest $request, int $id, DeletePendingAdminSale $action): JsonResponse
    {
        $sale = Sale::with('saleItems.product')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('cancel', $sale);

        return $action->handle($sale, trim((string) $request->input('reason')));
    }

    public function complete(int $id, CompleteAdminSale $action): JsonResponse
    {
        $sale = Sale::with('saleItems.product')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('complete', $sale);

        return $action->handle($sale);
    }

    public function markReady(int $id, MarkSaleReadyToPickup $action): JsonResponse
    {
        $sale = Sale::findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('markReady', $sale);

        return $action->handle($sale);
    }

    public function cancel(CancelAdminSaleRequest $request, int $id, CancelAdminSale $action): JsonResponse
    {
        $sale = Sale::with('saleItems.product')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('cancel', $sale);

        return $action->handle($sale, trim((string) $request->input('reason')));
    }

    public function returnSale(int $id, ReturnAdminSaleRequest $request, ReturnAdminSale $action): JsonResponse
    {
        $sale = Sale::with('saleItems.product')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('return', $sale);

        return $action->handle($sale, trim($request->reason));
    }
}
