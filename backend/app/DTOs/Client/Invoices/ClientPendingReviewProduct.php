<?php

namespace App\DTOs\Client\Invoices;

final readonly class ClientPendingReviewProduct
{
    public function __construct(
        public int $productId,
        public string $name,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'name' => $this->name,
        ];
    }
}
