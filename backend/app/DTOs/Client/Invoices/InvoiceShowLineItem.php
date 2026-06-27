<?php

namespace App\DTOs\Client\Invoices;

final readonly class InvoiceShowLineItem
{
    /**
     * @param  array{usesPlaceholder: bool, fallback: string, mobileWebp: ?string, placeholderIconClass: string}  $image
     */
    public function __construct(
        public int $productId,
        public string $name,
        public int $quantity,
        public string $unitPriceFormatted,
        public string $totalFormatted,
        public array $image,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unitPriceFormatted' => $this->unitPriceFormatted,
            'totalFormatted' => $this->totalFormatted,
            'image' => $this->image,
        ];
    }
}
