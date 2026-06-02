<?php

namespace App\Actions\Client\Cart;

use App\Data\Client\Cart\CartMutationResult;
use App\Services\Client\Cart\CartManager;

final class RemoveCartItem
{
    public function __construct(
        private CartManager $cart,
    ) {}

    public function handle(int $productId): CartMutationResult
    {
        $cart = $this->cart->lines();

        if ($cart === []) {
            return new CartMutationResult(false, 400, [
                'success' => false,
                'message' => 'El carrito está vacío',
                'cart_count' => 0,
                'cart_total' => 0,
            ]);
        }

        $cart = array_values(array_filter($cart, fn ($item) => $item['product_id'] != $productId));

        $this->cart->persist($cart);

        return new CartMutationResult(true, 200, [
            'success' => true,
            'message' => 'Producto eliminado del carrito',
            'cart_count' => $this->cart->totalItemCount(),
            'cart_total' => $this->cart->subtotal(),
        ]);
    }
}
