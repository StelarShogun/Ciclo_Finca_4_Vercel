<?php

namespace App\DTOs\Admin\SupplierOrders;

final readonly class SupplierOrderItemData
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
            quantity: max(1, (int) $data['quantity']),
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
        ];
    }
}
