<?php

namespace App\Services\Admin\Products;

use App\Http\Resources\Admin\ProductVariantResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SaleItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProductVariantService
{
    public function store(Product $product, array $validated): JsonResponse
    {
        $variantId = (int) $validated['variant_product_id'];
        $baseId = (int) $product->product_id;

        if ($variantId === $baseId) {
            return response()->json(['success' => false, 'message' => 'No puedes agregar el mismo producto como variante.'], 422);
        }

        if (ProductVariant::query()->where('base_product_id', $baseId)->where('variant_product_id', $variantId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Esa variante ya está asociada a este producto base.'], 422);
        }

        if (ProductVariant::query()->where('variant_product_id', $variantId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Esta variante ya está asociada a otro producto base.'], 422);
        }

        $variant = Product::query()->findOrFail($variantId);

        if (Product::canonicalStatus((string) ($variant->status ?? '')) !== 'active') {
            return response()->json(['success' => false, 'message' => 'Solo puedes agregar productos activos como variante.'], 422);
        }

        try {
            ProductVariant::create([
                'base_product_id' => $baseId,
                'variant_product_id' => $variantId,
            ]);
        } catch (QueryException $e) {
            if ((string) ($e->errorInfo[0] ?? '') === '23000') {
                return response()->json(['success' => false, 'message' => 'Esta variante ya está asociada a un producto base.'], 422);
            }

            throw $e;
        }

        $variant->sku_locked = SaleItem::query()->where('product_id', $variant->product_id)->exists();

        return response()->json([
            'success' => true,
            'message' => 'Variante agregada.',
            'variant' => ProductVariantResource::make($variant)->resolve(request()),
        ]);
    }

    public function update(Product $product, Product $variant, array $validated, bool $skuWasProvided): JsonResponse
    {
        if (! $this->linkExists($product, $variant)) {
            return response()->json(['success' => false, 'message' => 'La variante no existe o no pertenece a este producto base.'], 404);
        }

        $hasSales = SaleItem::query()->where('product_id', $variant->product_id)->exists();

        if ($skuWasProvided && $hasSales) {
            $current = $variant->sku;
            $normCurrent = ($current === null || $current === '') ? null : trim((string) $current);
            if ($normCurrent !== ($validated['sku'] ?? null)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede modificar el SKU porque esta variante ya tiene ventas asociadas.',
                ], 422);
            }
        }

        $variant->sale_price = $validated['sale_price'];
        $variant->stock_current = $validated['stock_current'];

        if ($skuWasProvided) {
            $variant->sku = $validated['sku'];
        }

        try {
            $variant->save();
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Revisa los datos enviados.',
                'errors' => $e->errors(),
            ], 422);
        }

        $variant->refresh();
        $variant->sku_locked = $hasSales;

        return response()->json([
            'success' => true,
            'message' => 'Variante actualizada correctamente.',
            'variant' => ProductVariantResource::make($variant)->resolve(request()),
        ]);
    }

    public function destroy(Product $product, Product $variant): JsonResponse
    {
        $link = ProductVariant::query()
            ->where('base_product_id', $product->product_id)
            ->where('variant_product_id', $variant->product_id)
            ->first();

        if (! $link) {
            return response()->json(['success' => false, 'message' => 'La variante no existe o no pertenece a este producto base.'], 404);
        }

        if ($this->variantHasActiveOrders((int) $variant->product_id)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la variante porque tiene pedidos activos o pendientes.',
            ], 409);
        }

        DB::transaction(function () use ($link, $variant): void {
            $link->delete();
            $variant->update(['status' => 'inactive']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Variante eliminada correctamente.',
        ]);
    }

    private function linkExists(Product $product, Product $variant): bool
    {
        return ProductVariant::query()
            ->where('base_product_id', $product->product_id)
            ->where('variant_product_id', $variant->product_id)
            ->exists();
    }

    private function variantHasActiveOrders(int $variantProductId): bool
    {
        $hasSupplierOrders = OrderItem::query()
            ->where('product_id', $variantProductId)
            ->whereHas('order', fn ($query) => $query->whereIn('state', ['draft', 'pending', 'confirmed', 'partial_received']))
            ->exists();

        return $hasSupplierOrders || SaleItem::query()
            ->where('product_id', $variantProductId)
            ->whereHas('sale', fn ($query) => $query->where('status', 'pending'))
            ->exists();
    }
}
