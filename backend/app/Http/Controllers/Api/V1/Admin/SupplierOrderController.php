<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\SupplierOrders\ClosePartialSupplierOrder;
use App\Actions\Admin\SupplierOrders\ListSupplierOrders;
use App\Actions\Admin\SupplierOrders\ReceiveSupplierOrder;
use App\Actions\Admin\SupplierOrders\SearchSupplierProducts;
use App\Actions\Admin\SupplierOrders\UpdateSupplierOrderState;
use App\DTOs\Admin\SupplierOrders\SupplierOrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupplierOrders\ClosePartialSupplierOrderRequest;
use App\Http\Requests\Admin\SupplierOrders\ReceiveSupplierOrderRequest;
use App\Http\Requests\Admin\SupplierOrders\SearchSupplierProductsRequest;
use App\Http\Requests\Admin\SupplierOrders\StoreSupplierOrderRequest;
use App\Http\Requests\Admin\SupplierOrders\SupplierOrderIndexRequest;
use App\Http\Requests\Admin\SupplierOrders\UpdateSupplierOrderStateRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\Admin\SupplierOrders\SupplierOrderQuery;
use App\Services\Admin\SupplierOrders\SupplierOrderWorkflowService;
use App\ViewModels\Admin\SupplierOrderIndexViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Pedidos a proveedores para el SPA Next. Reusa las Actions (estado y recepción
 * con transacciones de stock de entrada en SupplierOrderWorkflowService) y
 * SupplierOrderQuery. Modelo Order (PK num_order). Igual que el web Inertia.
 */
final class SupplierOrderController extends Controller
{
    public function index(SupplierOrderIndexRequest $request, ListSupplierOrders $action): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Order::class);

        return response()->json(['data' => SupplierOrderIndexViewModel::from($action->handle($request))]);
    }

    public function searchProducts(SearchSupplierProductsRequest $request, SearchSupplierProducts $action): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Product::class);

        return response()->json($action->handle($request));
    }

    public function show(int $id, SupplierOrderQuery $ordersQuery): JsonResponse
    {
        $order = Order::with(['supplier', 'orderItems', 'stateTimeline.admin'])->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $order);

        return response()->json(['data' => $ordersQuery->detailPayload($order)['order']]);
    }

    public function store(StoreSupplierOrderRequest $request, SupplierOrderWorkflowService $workflow, SupplierOrderQuery $ordersQuery): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Order::class);

        $order = $workflow->createOrder(SupplierOrderData::fromArray($request->validated())->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Pedido creado correctamente.',
            'data' => $ordersQuery->detailPayload($order)['order'],
        ], 201);
    }

    public function updateState(UpdateSupplierOrderStateRequest $request, int $id, UpdateSupplierOrderState $action): JsonResponse
    {
        $order = Order::findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize(
            $request->input('state') === 'cancelled' ? 'cancel' : 'update',
            $order,
        );

        return $action->handle($order, $request->validated());
    }

    public function closePartial(ClosePartialSupplierOrderRequest $request, int $id, ClosePartialSupplierOrder $action): JsonResponse
    {
        $order = Order::with('orderItems')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('supplier-orders.close-partial', $order);

        return $action->handle($order, (string) $request->validated('reason'));
    }

    public function receive(ReceiveSupplierOrderRequest $request, int $id, ReceiveSupplierOrder $action): JsonResponse
    {
        $order = Order::with('orderItems')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('supplier-orders.receive', $order);

        return $action->handle($order, $request->validated());
    }
}
