<?php

namespace App\DTOs\Admin\Sales;

final readonly class SaleItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public float $unitPrice,
        public float $total,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) ($data['product_id'] ?? $data['producto_id']),
            quantity: max(1, (int) ($data['quantity'] ?? $data['cantidad'] ?? 1)),
            unitPrice: round((float) ($data['precio_unitario'] ?? $data['unit_price'] ?? 0), 2),
            total: round((float) ($data['total'] ?? 0), 2),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'precio_unitario' => $this->unitPrice,
            'total' => $this->total,
        ];
    }
}
