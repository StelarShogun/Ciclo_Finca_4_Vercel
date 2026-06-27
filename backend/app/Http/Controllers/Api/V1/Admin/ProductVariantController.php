<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\StoreProductVariantRequest;
use App\Http\Requests\Admin\Products\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Admin\Products\ProductVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Variantes de producto para el SPA Next. Las variantes son productos
 * existentes enlazados (variant_product_id). Reusa ProductVariantService, que
 * ya devuelve JsonResponse. Espeja el controller web.
 */
final class ProductVariantController extends Controller
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

        return $variants->update($product, $variant, $request->validated(), $request->has('sku'));
    }

    public function destroy(Product $product, Product $variant, ProductVariantService $variants): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);
        Gate::forUser(Auth::guard('admin')->user())->authorize('delete', $variant);

        return $variants->destroy($product, $variant);
    }
}
