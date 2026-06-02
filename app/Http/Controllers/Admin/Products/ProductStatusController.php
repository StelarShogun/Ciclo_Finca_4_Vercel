<?php

namespace App\Http\Controllers\Admin\Products;

use App\Actions\Admin\Products\ActivateProduct;
use App\Actions\Admin\Products\DeactivateProduct;
use App\Actions\Admin\Products\ForceDeleteProduct;
use App\Http\Controllers\Controller;

final class ProductStatusController extends Controller
{
    public function destroy(int $id, DeactivateProduct $deactivateProduct)
    {
        try {
            $payload = $deactivateProduct->handle($id);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json($payload);
            }

            return redirect()->route('inventory')->with('status', $payload['message']);
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deactivating product: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error deactivating product');
        }
    }

    public function activate(int $id, ActivateProduct $activateProduct)
    {
        try {
            $payload = $activateProduct->handle($id);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json($payload);
            }

            return redirect()->route('inventory')->with('status', 'Product activated successfully');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error activating product: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error activating product');
        }
    }

    public function forceDelete(int $id, ForceDeleteProduct $forceDeleteProduct)
    {
        try {
            $payload = $forceDeleteProduct->handle($id);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json($payload);
            }

            return redirect()->route('inventory')->with('status', $payload['message']);
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting product: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error deleting product');
        }
    }
}
