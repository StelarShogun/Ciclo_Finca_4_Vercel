<?php

namespace App\Actions\Client\Cart;

use App\Data\Client\Cart\CartMutationResult;
use App\Models\Product;
use App\Services\Client\Cart\CartManager;
use Illuminate\Http\Request;

final class UpdateCartItem
{
    public function __construct(
        private CartManager $cart,
    ) {}

    public function handle(Request $request): CartMutationResult
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (! $product->isPurchasableByClient()) {
            return new CartMutationResult(false, 400, [
                'success' => false,
                'message' => Product::MSG_CLIENT_AGOTADO,
            ]);
        }

        $requestedQty = (int) $request->quantity;

        if ($product->stock_current < 1 || $requestedQty > (int) $product->stock_current) {
            return new CartMutationResult(false, 400, [
                'success' => false,
                'message' => $product->stock_current < 1
                    ? Product::MSG_CLIENT_AGOTADO
                    : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
            ]);
        }

        $cart = $this->cart->lines();
        $lineSubtotal = 0.0;
        $found = false;

        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $request->product_id) {
                $cart[$index] = [
                    'product_id' => (int) $item['product_id'],
                    'name' => (string) ($item['name'] ?? ''),
                    'price' => (float) $item['price'],
                    'quantity' => $requestedQty,
                    'image' => (string) ($item['image'] ?? ''),
                ];
                $unitPrice = (float) $item['price'];
                $lineSubtotal = $unitPrice * $requestedQty;
                $found = true;
                break;
            }
        }

        if (! $found) {
            return new CartMutationResult(false, 404, [
                'success' => false,
                'message' => 'El producto no está en el carrito',
            ]);
        }

        $this->cart->persist($cart);

        return new CartMutationResult(true, 200, [
            'success' => true,
            'message' => 'Carrito actualizado',
            'cart_count' => $this->cart->totalItemCount(),
            'cart_total' => $this->cart->subtotal(),
            'line_subtotal' => $lineSubtotal,
            'quantity_applied' => $requestedQty,
            'stock_clamped' => false,
        ]);
    }
}
