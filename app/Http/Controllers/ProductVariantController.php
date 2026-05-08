<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SaleItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'variant_product_id' => ['required', 'integer', 'exists:products,product_id'],
        ], [
            'variant_product_id.required' => 'Selecciona una variante.',
            'variant_product_id.exists' => 'La variante seleccionada no existe.',
        ]);

        $variantId = (int) $validated['variant_product_id'];
        $baseId = (int) $product->product_id;

        if ($variantId === $baseId) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes agregar el mismo producto como variante.',
            ], 422);
        }

        $already = ProductVariant::query()
            ->where('base_product_id', $baseId)
            ->where('variant_product_id', $variantId)
            ->exists();

        if ($already) {
            return response()->json([
                'success' => false,
                'message' => 'Esa variante ya está asociada a este producto base.',
            ], 422);
        }

        $linkedElsewhere = ProductVariant::query()
            ->where('variant_product_id', $variantId)
            ->exists();

        if ($linkedElsewhere) {
            return response()->json([
                'success' => false,
                'message' => 'Esta variante ya está asociada a otro producto base.',
            ], 422);
        }

        $variant = Product::query()->findOrFail($variantId);

        // Keep it simple for admin: only allow linking active variants.
        if (Product::canonicalStatus((string) ($variant->status ?? '')) !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes agregar productos activos como variante.',
            ], 422);
        }

        try {
            ProductVariant::create([
                'base_product_id' => $baseId,
                'variant_product_id' => $variantId,
            ]);
        } catch (QueryException $e) {
            // Handle race conditions against unique constraints (pair uniqueness / single-base-per-variant).
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            if ($sqlState === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta variante ya está asociada a un producto base.',
                ], 422);
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Variante agregada.',
            'variant' => [
                'product_id' => (int) $variant->product_id,
                'name' => (string) $variant->name,
                'status' => (string) $variant->status,
                'stock_current' => (int) $variant->stock_current,
                'sale_price' => (string) $variant->sale_price,
                'sku' => $variant->displaySku(),
                'sku_custom' => $variant->sku,
                'sku_locked' => SaleItem::query()->where('product_id', $variant->product_id)->exists(),
            ],
        ]);
    }

    public function update(Request $request, Product $product, Product $variant): JsonResponse
    {
        $link = ProductVariant::query()
            ->where('base_product_id', $product->product_id)
            ->where('variant_product_id', $variant->product_id)
            ->first();

        if (! $link) {
            return response()->json([
                'success' => false,
                'message' => 'La variante no existe o no pertenece a este producto base.',
            ], 404);
        }

        $hasSales = SaleItem::query()->where('product_id', $variant->product_id)->exists();

        $skuWasProvided = $request->has('sku');
        if ($skuWasProvided) {
            $raw = $request->input('sku');
            $normalizedSku = null;
            if (is_string($raw)) {
                $t = trim($raw);
                $normalizedSku = $t === '' ? null : $t;
            }
            $request->merge(['sku' => $normalizedSku]);
        }

        if ($skuWasProvided && $hasSales) {
            $current = $variant->sku;
            $normCurrent = ($current === null || $current === '') ? null : trim((string) $current);
            $normIncoming = $request->input('sku');
            if ($normCurrent !== $normIncoming) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede modificar el SKU porque esta variante ya tiene ventas asociadas.',
                ], 422);
            }
        }

        $rules = [
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_current' => ['required', 'integer', 'min:0'],
        ];

        if ($skuWasProvided) {
            $rules['sku'] = [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('products', 'sku')->ignore($variant->product_id, 'product_id'),
            ];
        }

        try {
            $validated = $request->validate($rules, [
                'sale_price.required' => 'El precio es obligatorio.',
                'stock_current.required' => 'El stock es obligatorio.',
                'sku.regex' => 'El SKU solo puede contener letras, números y guiones.',
                'sku.unique' => 'Ese SKU ya está en uso por otro producto.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Revisa los datos enviados.',
                'errors' => $e->errors(),
            ], 422);
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

        return response()->json([
            'success' => true,
            'message' => 'Variante actualizada correctamente.',
            'variant' => [
                'product_id' => (int) $variant->product_id,
                'name' => (string) $variant->name,
                'status' => (string) $variant->status,
                'stock_current' => (int) $variant->stock_current,
                'sale_price' => (string) $variant->sale_price,
                'sku' => $variant->displaySku(),
                'sku_custom' => $variant->sku,
                'sku_locked' => $hasSales,
            ],
        ]);
    }

    public function destroy(Product $product, Product $variant): JsonResponse
    {
        $link = ProductVariant::query()
            ->where('base_product_id', $product->product_id)
            ->where('variant_product_id', $variant->product_id)
            ->first();

        if (! $link) {
            return response()->json([
                'success' => false,
                'message' => 'La variante no existe o no pertenece a este producto base.',
            ], 404);
        }

        if ($this->variantHasActiveOrders((int) $variant->product_id)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la variante porque tiene pedidos activos o pendientes.',
            ], 409);
        }

        DB::transaction(function () use ($link, $variant): void {
            // Remove the variant association and deactivate the variant product record.
            $link->delete();
            $variant->update(['status' => 'inactive']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Variante eliminada correctamente.',
        ]);
    }

    private function variantHasActiveOrders(int $variantProductId): bool
    {
        // Supplier orders: any order not delivered/cancelled blocks deletion.
        $supplierOrderStates = $this->activeSupplierOrderStates();
        $hasSupplierOrders = OrderItem::query()
            ->where('product_id', $variantProductId)
            ->whereHas('order', function ($q) use ($supplierOrderStates) {
                $q->whereIn('state', $supplierOrderStates);
            })
            ->exists();

        if ($hasSupplierOrders) {
            return true;
        }

        // Client sales: pending sales block deletion (still active).
        return SaleItem::query()
            ->where('product_id', $variantProductId)
            ->whereHas('sale', fn ($q) => $q->where('status', 'pending'))
            ->exists();
    }

    private function activeSupplierOrderStates(): array
    {
        // Block deletion for supplier orders whose persisted states are still active/in-progress.
        // Persisted order states checked here include: draft, pending, confirmed, delivered, cancelled.
        // Also include partial_received as it is used during receiving flows.
        return ['draft', 'pending', 'confirmed', 'partial_received'];
    }
}
