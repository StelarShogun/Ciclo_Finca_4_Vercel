<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\StoreProductVariantRequest;
use App\Http\Requests\Admin\Products\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Admin\Products\ProductVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    public function store(StoreProductVariantRequest $request, Product $product, ProductVariantService $variants): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', ProductVariant::class);

        return $variants->store($product, $request->validated());
    }

    public function update(UpdateProductVariantRequest $request, Product $product, Product $variant, ProductVariantService $variants): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $variant);

        $skuWasProvided = $request->has('sku');

        try {
            $validated = $request->validated();
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Revisa los datos enviados.',
                'errors' => $e->errors(),
            ], 422);
        }

        return $variants->update($product, $variant, $validated, $skuWasProvided);
    }

    public function destroy(Product $product, Product $variant, ProductVariantService $variants): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);
        Gate::forUser(Auth::guard('admin')->user())->authorize('delete', $variant);

        return $variants->destroy($product, $variant);
    }
}
