<?php

namespace App\Actions\Client\Cart;

use App\Data\Client\Cart\CartMutationResult;
use App\Services\Client\Cart\CartManager;

final class ClearCart
{
    public function __construct(
        private CartManager $cart,
    ) {}

    public function handle(): CartMutationResult
    {
        $this->cart->clear();

        return new CartMutationResult(true, 200, [
            'success' => true,
            'message' => 'Carrito vaciado',
            'cart_count' => 0,
            'cart_total' => 0.0,
        ]);
    }
}
