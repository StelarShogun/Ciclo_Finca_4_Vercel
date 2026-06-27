<?php

namespace App\Actions\Client\Cart;

use App\DTOs\Client\Cart\CartMutationResult;

final class AddProductToCart
{
    public function __construct(private AddCartItem $addCartItem) {}

    /**
     * @param  array{product_id:int, quantity:int}  $data
     */
    public function handle(array $data): CartMutationResult
    {
        return $this->addCartItem->handle($data);
    }
}
