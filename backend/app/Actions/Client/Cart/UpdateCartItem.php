<?php

namespace App\Actions\Client\Cart;

use App\DTOs\Client\Cart\CartMutationResult;
use App\Models\Product;
use App\Services\Client\Cart\CartManager;

final class UpdateCartItem
{
    public function __construct(
        private CartManager $cart,
    ) {}

    /**
     * @param  array{product_id:int, quantity:int}  $data
     */
    public function handle(array $data): CartMutationResult
    {
        $productId = (int) $data['product_id'];
        $requestedQty = (int) $data['quantity'];

        $product = Product::findOrFail($productId);

        if (! $product->isPurchasableByClient()) {
            return new CartMutationResult(false, 400, [
                'success' => false,
                'message' => Product::MSG_CLIENT_AGOTADO,
            ]);
        }

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
            if ($item['product_id'] == $productId) {
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
