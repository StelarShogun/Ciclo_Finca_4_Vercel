<?php

namespace App\DTOs\Client\Invoices;

final readonly class InvoiceShowTotals
{
    public function __construct(
        public string $subtotalFormatted,
        public string $ivaFormatted,
        public string $discountFormatted,
        public string $totalFormatted,
        public int $itemsCount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subtotalFormatted' => $this->subtotalFormatted,
            'ivaFormatted' => $this->ivaFormatted,
            'discountFormatted' => $this->discountFormatted,
            'totalFormatted' => $this->totalFormatted,
            'itemsCount' => $this->itemsCount,
        ];
    }
}
