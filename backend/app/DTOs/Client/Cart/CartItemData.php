<?php

namespace App\DTOs\Client\Cart;

final readonly class CartItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            quantity: max(1, (int) ($data['quantity'] ?? 1)),
        );
    }
}
