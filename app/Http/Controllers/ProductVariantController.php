<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $variant = Product::query()->findOrFail($variantId);

        // Keep it simple for admin: only allow linking active variants.
        if (Product::canonicalStatus((string) ($variant->status ?? '')) !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes agregar productos activos como variante.',
            ], 422);
        }

        ProductVariant::create([
            'base_product_id' => $baseId,
            'variant_product_id' => $variantId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Variante agregada.',
            'variant' => [
                'product_id' => (int) $variant->product_id,
                'name' => (string) $variant->name,
                'status' => (string) $variant->status,
                'stock_current' => (int) $variant->stock_current,
                'sale_price' => (string) $variant->sale_price,
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
        $defined = [];
        $connection = Order::query()->getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support native ENUMs; treat as free-form.
            $defined = ['draft', 'pending', 'confirmed', 'partial_received'];
        } else {
            // Best-effort: try to include known states; if schema evolved, include partial_received too.
            $defined = ['draft', 'pending', 'confirmed', 'partial_received'];
        }

        return $defined;
    }
}

