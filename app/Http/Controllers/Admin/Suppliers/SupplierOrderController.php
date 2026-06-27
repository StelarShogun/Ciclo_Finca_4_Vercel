<?php

namespace App\Http\Controllers\Admin\Suppliers;

use App\Actions\Admin\SupplierOrders\ClosePartialSupplierOrder;
use App\Actions\Admin\SupplierOrders\CreateSupplierOrder;
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
use App\Models\Supplier;
use App\Services\Admin\SupplierOrders\SupplierOrderQuery;
use App\ViewModels\Admin\SupplierOrderIndexViewModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SupplierOrderController extends Controller
{
    public function searchProducts(SearchSupplierProductsRequest $request, SearchSupplierProducts $action)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Product::class);

        return response()->json($action->handle($request));
    }

    public function index(SupplierOrderIndexRequest $request, ListSupplierOrders $action)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Order::class);

        return Inertia::render('Admin/SupplierOrders/Index', SupplierOrderIndexViewModel::from($action->handle($request)));
    }

    public function detail(int $id, SupplierOrderQuery $ordersQuery)
    {
        $order = Order::with(['supplier', 'orderItems', 'stateTimeline.admin'])
            ->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $order);

        return Inertia::render('Admin/SupplierOrders/Detail', $ordersQuery->detailPayload($order));
    }

    public function store(StoreSupplierOrderRequest $request, CreateSupplierOrder $action)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Order::class);

        return $action->handle(SupplierOrderData::fromArray($request->validated())->toArray());
    }

    public function show(int $id, SupplierOrderQuery $ordersQuery)
    {
        $order = Order::with(['supplier', 'orderItems', 'stateTimeline.admin'])
            ->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $order);

        return response()->json($ordersQuery->showPayload($order));
    }

    public function updateState(
        UpdateSupplierOrderStateRequest $request,
        int $id,
        UpdateSupplierOrderState $action,
    ) {
        $order = Order::findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize(
            $request->input('state') === 'cancelled' ? 'cancel' : 'update',
            $order,
        );

        return $action->handle($order, $request->validated());
    }

    public function closePartial(ClosePartialSupplierOrderRequest $request, int $id, ClosePartialSupplierOrder $action)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('supplier-orders.close-partial', $order);

        return $action->handle($order, (string) $request->validated('reason'));
    }

    public function receiveOrder(ReceiveSupplierOrderRequest $request, int $id, ReceiveSupplierOrder $action)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('supplier-orders.receive', $order);

        return $action->handle($order, $request->validated());
    }

    public function supplierDetails(int $id, SupplierOrderQuery $ordersQuery)
    {
        $supplier = Supplier::withCount(['products' => fn ($query) => $query->where('status', 'active')])
            ->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $supplier);

        return response()->json($ordersQuery->supplierPayload($supplier));
    }
}
