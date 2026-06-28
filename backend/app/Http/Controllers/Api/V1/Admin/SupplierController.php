<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Suppliers\StoreSupplierRequest;
use App\Http\Requests\Admin\Suppliers\UpdateSupplierRequest;
use App\Http\Resources\Admin\SupplierResource;
use App\Models\Supplier;
use App\Services\Admin\Suppliers\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Proveedores admin para el SPA Next. Reusa SupplierService (índice, alta,
 * edición, borrado) y SupplierResource. CRUD simple con validación en las
 * FormRequests existentes.
 */
final class SupplierController extends Controller
{
    public function index(Request $request, SupplierService $suppliers): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Supplier::class);

        return response()->json(['data' => $suppliers->indexPayload($request->query())]);
    }

    public function store(StoreSupplierRequest $request, SupplierService $suppliers): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Supplier::class);

        try {
            $supplier = $suppliers->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Proveedor registrado exitosamente.',
                'data' => (new SupplierResource($supplier))->resolve(),
            ], 201);
        } catch (\Throwable $exception) {
            $suppliers->logFailure('supplier_store_failed', $exception);

            return response()->json(['success' => false, 'message' => 'No se pudo registrar el proveedor. Inténtalo nuevamente.'], 500);
        }
    }

    public function show(int $id, SupplierService $suppliers): JsonResponse
    {
        $supplier = Supplier::query()->find($id);
        if (! $supplier) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $supplier);

        return response()->json($suppliers->jsonPayload($supplier));
    }

    public function update(UpdateSupplierRequest $request, int $id, SupplierService $suppliers): JsonResponse
    {
        $supplier = Supplier::query()->find($id);
        if (! $supplier) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $supplier);

        try {
            $updated = $suppliers->update($supplier, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente.',
                'data' => (new SupplierResource($updated))->resolve(),
            ]);
        } catch (\Throwable $exception) {
            $suppliers->logFailure('supplier_update_failed', $exception, $supplier);

            return response()->json(['success' => false, 'message' => 'No se pudo actualizar el proveedor. Inténtalo nuevamente.'], 500);
        }
    }

    public function destroy(int $id, SupplierService $suppliers): JsonResponse
    {
        $supplier = Supplier::query()->find($id);
        if (! $supplier) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        Gate::forUser(Auth::guard('admin')->user())->authorize('delete', $supplier);

        try {
            $suppliers->delete($supplier);

            return response()->json(['success' => true, 'message' => 'Proveedor eliminado exitosamente.']);
        } catch (\Throwable $exception) {
            $suppliers->logFailure('supplier_delete_failed', $exception, $supplier);

            return response()->json(['success' => false, 'message' => 'No se pudo completar la acción. Inténtalo nuevamente.'], 500);
        }
    }
}
