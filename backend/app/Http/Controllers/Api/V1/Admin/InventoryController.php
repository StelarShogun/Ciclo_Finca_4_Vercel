<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\Products\UpdateManualStock;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\InventoryMovementIndexRequest;
use App\Http\Requests\Admin\Products\ManualStockAdjustmentRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Admin\Inventory\InventoryMovementQuery;
use App\Services\Admin\Products\ProductPayloadBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Inventario admin para el SPA Next. Lista de stock (ProductPayloadBuilder),
 * ajustes manuales de stock (UpdateManualStock → InventoryMovementService, que
 * registra el movimiento) e historial de movimientos por producto.
 */
final class InventoryController extends Controller
{
    public function index(Request $request, ProductPayloadBuilder $payloads): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Product::class);

        return response()->json(['data' => $payloads->inventoryIndex($request)]);
    }

    public function addStock(ManualStockAdjustmentRequest $request, int $id, UpdateManualStock $action): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Product::class);

        return $this->runStock(fn () => $action->add($request, $id));
    }

    public function removeStock(ManualStockAdjustmentRequest $request, int $id, UpdateManualStock $action): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Product::class);

        return $this->runStock(fn () => $action->remove($request, $id));
    }

    public function movements(InventoryMovementIndexRequest $request, int $id, InventoryMovementQuery $query): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', InventoryMovement::class);

        $product = Product::query()->findOrFail($id);

        return response()->json($query->jsonPayload($product, $request->validated()));
    }

    /** Envuelve los ajustes manuales con el mismo manejo de errores del controller web. */
    private function runStock(callable $fn): JsonResponse
    {
        try {
            return response()->json($fn());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado.'], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }
}
