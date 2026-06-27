<?php

namespace App\Http\Controllers\Admin\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Suppliers\StoreSupplierRequest;
use App\Http\Requests\Admin\Suppliers\UpdateSupplierRequest;
use App\Http\Resources\Admin\SupplierResource;
use App\Models\Supplier;
use App\Services\Admin\Suppliers\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class SupplierController extends Controller
{
    public function index(Request $request, SupplierService $suppliers)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Supplier::class);

        return Inertia::render('Admin/Suppliers/Index', $suppliers->indexPayload($request->query()));
    }

    public function create(): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Supplier::class);

        return redirect()->route('suppliers.index', ['open' => 'new']);
    }

    public function store(StoreSupplierRequest $request, SupplierService $suppliers): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Supplier::class);

        try {
            $supplier = $suppliers->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Proveedor registrado exitosamente.',
                'redirect' => route('suppliers.index'),
                'data' => (new SupplierResource($supplier))->resolve(),
            ], 201);
        } catch (\Throwable $exception) {
            $suppliers->logFailure('supplier_store_failed', $exception);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar el proveedor. Inténtalo nuevamente.',
            ], 500);
        }
    }

    public function show(Request $request, SupplierService $suppliers, string $supplier): RedirectResponse|JsonResponse
    {
        $model = Supplier::query()->find($supplier);
        if (! $model) {
            return $this->missingSupplierResponse($request);
        }

        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $model);

        return $request->expectsJson()
            ? response()->json($suppliers->jsonPayload($model))
            : redirect()->route('suppliers.index');
    }

    public function edit(string $supplier): RedirectResponse
    {
        $model = Supplier::query()->findOrFail($supplier);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $model);

        return redirect()->route('suppliers.index', ['edit' => $supplier]);
    }

    public function update(UpdateSupplierRequest $request, SupplierService $suppliers, string $supplier): JsonResponse
    {
        $model = Supplier::query()->find($supplier);
        if (! $model) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $model);

        try {
            $updated = $suppliers->update($model, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente.',
                'redirect' => route('suppliers.index'),
                'data' => (new SupplierResource($updated))->resolve(),
            ]);
        } catch (\Throwable $exception) {
            $suppliers->logFailure('supplier_update_failed', $exception, $model);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el proveedor. Inténtalo nuevamente.',
            ], 500);
        }
    }

    public function destroy(Request $request, SupplierService $suppliers, string $supplier): RedirectResponse|JsonResponse
    {
        $model = Supplier::query()->find($supplier);
        if (! $model) {
            return $this->missingSupplierResponse($request);
        }

        Gate::forUser(Auth::guard('admin')->user())->authorize('delete', $model);

        try {
            $suppliers->delete($model);

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => 'Proveedor eliminado exitosamente.'])
                : redirect()->route('suppliers.index')->with('status', 'Proveedor eliminado exitosamente.');
        } catch (\Throwable $exception) {
            $suppliers->logFailure('supplier_delete_failed', $exception, $model);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'No se pudo completar la acción. Inténtalo nuevamente.'], 500)
                : redirect()->route('suppliers.index')->with('error', 'No se pudo completar la acción. Inténtalo nuevamente.');
        }
    }

    private function missingSupplierResponse(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        return redirect()->route('suppliers.index')->with('error', 'Proveedor no encontrado.');
    }
}
