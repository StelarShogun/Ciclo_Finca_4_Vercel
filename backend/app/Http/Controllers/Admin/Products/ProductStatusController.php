<?php

namespace App\Http\Controllers\Admin\Products;

use App\Actions\Admin\Products\ActivateProduct;
use App\Actions\Admin\Products\DeactivateProduct;
use App\Actions\Admin\Products\ForceDeleteProduct;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Shared\Security\SensitiveDataMasker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class ProductStatusController extends Controller
{
    public function destroy(int $id, DeactivateProduct $deactivateProduct)
    {
        try {
            $product = Product::query()->findOrFail($id);
            Gate::forUser(Auth::guard('admin')->user())->authorize('delete', $product);

            $payload = $deactivateProduct->handle($id);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json($payload);
            }

            return redirect()->route('inventory')->with('status', $payload['message']);
        } catch (\Throwable $e) {
            Log::error('product_deactivate_failed', SensitiveDataMasker::exceptionContext($e, [
                'product_id' => $id,
            ]));

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo desactivar el producto. Inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->back()->with('error', 'No se pudo desactivar el producto. Inténtalo de nuevo.');
        }
    }

    public function activate(int $id, ActivateProduct $activateProduct)
    {
        try {
            $product = Product::query()->findOrFail($id);
            Gate::forUser(Auth::guard('admin')->user())->authorize('toggle', $product);

            $payload = $activateProduct->handle($id);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json($payload);
            }

            return redirect()->route('inventory')->with('status', 'Product activated successfully');
        } catch (\Throwable $e) {
            Log::error('product_activate_failed', SensitiveDataMasker::exceptionContext($e, [
                'product_id' => $id,
            ]));

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo activar el producto. Inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->back()->with('error', 'No se pudo activar el producto. Inténtalo de nuevo.');
        }
    }

    public function forceDelete(int $id, ForceDeleteProduct $forceDeleteProduct)
    {
        try {
            $product = Product::query()->findOrFail($id);
            Gate::forUser(Auth::guard('admin')->user())->authorize('forceDelete', $product);

            $payload = $forceDeleteProduct->handle($id);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json($payload);
            }

            return redirect()->route('inventory')->with('status', $payload['message']);
        } catch (\Throwable $e) {
            Log::error('product_force_delete_failed', SensitiveDataMasker::exceptionContext($e, [
                'product_id' => $id,
            ]));

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo eliminar el producto. Inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->back()->with('error', 'No se pudo eliminar el producto. Inténtalo de nuevo.');
        }
    }
}
