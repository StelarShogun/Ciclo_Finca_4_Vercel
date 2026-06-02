<?php

namespace App\Http\Controllers\Admin\Products;

use App\Actions\Admin\Products\AddProductManualStock;
use App\Actions\Admin\Products\RemoveProductManualStock;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\ManualStockAdjustmentRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductManualStockController extends Controller
{
    public function add(ManualStockAdjustmentRequest $request, int $id, AddProductManualStock $action)
    {
        try {
            return response()->json($action->handle($request, $id));
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('addManualStock error', ['product_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al actualizar el stock. Inténtalo de nuevo.',
            ], 500);
        }
    }

    public function remove(ManualStockAdjustmentRequest $request, int $id, RemoveProductManualStock $action)
    {
        try {
            return response()->json($action->handle($request, $id));
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('removeManualStock error', ['product_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al actualizar el stock. Inténtalo de nuevo.',
            ], 500);
        }
    }
}
