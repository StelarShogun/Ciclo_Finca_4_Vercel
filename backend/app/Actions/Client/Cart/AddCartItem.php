<?php

namespace App\Actions\Client\Cart;

use App\DTOs\Client\Cart\CartMutationResult;
use App\Models\Product;
use App\Services\Client\Cart\CartManager;

final class AddCartItem
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
        $quantity = (int) $data['quantity'];

        $product = Product::findOrFail($productId);

        if (! $product->isPurchasableByClient()) {
            return new CartMutationResult(false, 400, [
                'success' => false,
                'message' => Product::MSG_CLIENT_AGOTADO,
            ]);
        }

        if ($product->stock_current < $quantity) {
            return new CartMutationResult(false, 400, [
                'success' => false,
                'message' => $product->stock_current < 1
                    ? Product::MSG_CLIENT_AGOTADO
                    : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
            ]);
        }

        $cart = $this->cart->lines();
        $existingIndex = null;

        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $productId) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            $newQuantity = ($cart[$existingIndex]['quantity'] ?? 0) + $quantity;

            if ($newQuantity > $product->stock_current) {
                return new CartMutationResult(false, 400, [
                    'success' => false,
                    'message' => $product->stock_current < 1
                        ? Product::MSG_CLIENT_AGOTADO
                        : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
                ]);
            }

            $cart[$existingIndex]['quantity'] = $newQuantity;
        } else {
            $mediaUrl = $product->getFirstMediaUrl('main_image');
            $cart[] = [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->sale_price,
                'quantity' => $quantity,
                'image' => $mediaUrl,
            ];
        }

        $this->cart->persist($cart);

        return new CartMutationResult(true, 200, [
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'cart_count' => $this->cart->totalItemCount(),
            'cart_total' => $this->cart->subtotal(),
        ]);
    }
}
