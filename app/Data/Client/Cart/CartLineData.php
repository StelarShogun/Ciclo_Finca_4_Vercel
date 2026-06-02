<?php

namespace App\Data\Client\Cart;

/**
 * Session cart row (minimal keys persisted in session and cart_items).
 */
final readonly class CartLineData
{
    public function __construct(
        public int $productId,
        public string $name,
        public float $price,
        public int $quantity,
        public string $image = '',
    ) {}

    /**
     * @return array{product_id: int, name: string, price: float, quantity: int, image: string}
     */
    public function toSessionArray(): array
    {
        return [
            'product_id' => $this->productId,
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'image' => $this->image,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function fromSessionArray(array $item): ?self
    {
        if (! isset($item['product_id'])) {
            return null;
        }

        return new self(
            productId: (int) $item['product_id'],
            name: (string) ($item['name'] ?? ''),
            price: (float) ($item['price'] ?? 0),
            quantity: (int) ($item['quantity'] ?? 0),
            image: (string) ($item['image'] ?? ''),
        );
    }
}
