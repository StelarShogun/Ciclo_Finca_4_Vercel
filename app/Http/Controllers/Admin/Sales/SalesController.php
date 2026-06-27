<?php

namespace App\Http\Controllers\Admin\Sales;

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
use App\Services\Admin\Sales\AdminSalesExportService;
use App\Services\Admin\Sales\AdminSalesQuery;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\ViewModels\Admin\SalesIndexViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SalesController extends Controller
{
    public function index(AdminSalesIndexRequest $request, AdminSalesQuery $salesQuery)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        return Inertia::render('Admin/Sales/Index', SalesIndexViewModel::from($salesQuery->indexPayload($request)));
    }

    public function historyHeartbeat(Request $request, AdminSalesQuery $salesQuery)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        return response()->json($salesQuery->heartbeatPayload((int) $request->query('since', 0)));
    }

    public function show(Request $request, int $id)
    {
        try {
            $sale = Sale::with(['client', 'sellerAdmin', 'saleItems.product'])->findOrFail($id);
            Gate::forUser(Auth::guard('admin')->user())->authorize('view', $sale);

            return response()->json([
                'success' => true,
                'sale' => SaleResource::make($sale)->resolve($request),
            ]);
        } catch (\Throwable $e) {
            return $this->technicalJsonError($e, 'sale_show_failed', 'No fue posible cargar la venta.');
        }
    }

    public function store(StoreAdminSaleRequest $request, CreateAdminSale $action)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Sale::class);

        return $action->handle(AdminSaleData::fromArray($request->validated())->toArray());
    }

    public function update(UpdateAdminSaleRequest $request, int $id, UpdateAdminSale $action)
    {
        $sale = Sale::findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $sale);

        return $action->handle($sale, $request->validated());
    }

    public function destroy(CancelAdminSaleRequest $request, int $id, DeletePendingAdminSale $action)
    {
        $sale = Sale::with('saleItems.product')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('cancel', $sale);

        return $action->handle($sale, trim((string) $request->input('reason')));
    }

    // Complete a ready-to-pickup order without duplicating stock output movements.
    public function complete(int $id, CompleteAdminSale $action)
    {
        $sale = Sale::with('saleItems.product')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('complete', $sale);

        return $action->handle($sale);
    }

    public function markReadyToPickup(int $id, MarkSaleReadyToPickup $action): JsonResponse
    {
        $sale = Sale::findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('markReady', $sale);

        return $action->handle($sale);
    }

    public function cancel(CancelAdminSaleRequest $request, int $id, CancelAdminSale $action)
    {
        $sale = Sale::with('saleItems.product')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('cancel', $sale);

        return $action->handle($sale, trim((string) $request->input('reason')));
    }

    public function returnSale(int $id, ReturnAdminSaleRequest $request, ReturnAdminSale $action)
    {
        $sale = Sale::with('saleItems.product')->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('return', $sale);

        return $action->handle($sale, trim($request->reason));
    }

    public function print(int $id)
    {
        $sale = Sale::with(['client', 'sellerAdmin', 'saleItems.product'])->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $sale);

        return view('admin.sales.print', compact('sale'));
    }

    public function invoice(int $id)
    {
        $sale = Sale::with(['client', 'sellerAdmin', 'saleItems.product'])->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $sale);

        if ($sale->status !== 'completed') {
            abort(403, 'La factura solo está disponible para ventas confirmadas.');
        }

        return view('admin.sales.invoice', compact('sale'));
    }

    public function export(Request $request, AdminSalesQuery $salesQuery, AdminSalesExportService $salesExport)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('export', Sale::class);

        try {
            return $salesExport->download($request, $salesQuery);
        } catch (\Throwable $e) {
            return $this->technicalJsonError($e, 'sales_export_failed', 'No fue posible exportar las ventas.');
        }
    }

    private function technicalJsonError(\Throwable $e, string $event, string $message): JsonResponse
    {
        Log::error($event, SensitiveDataMasker::exceptionContext($e, [
            'exception' => $e::class,
            'admin_id' => Auth::guard('admin')->id(),
        ]));

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }
}
