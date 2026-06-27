<?php

namespace App\DTOs\Client\Invoices;

final readonly class InvoiceShowOrderMeta
{
    public function __construct(
        public int $saleId,
        public string $saleDateLabel,
        public string $statusLabel,
        public string $statusPillClass,
        public string $statusIconClass,
        public ?string $cancellationReason,
        public string $paymentDisplay,
        public string $sourceDisplay,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'saleId' => $this->saleId,
            'saleDateLabel' => $this->saleDateLabel,
            'statusLabel' => $this->statusLabel,
            'statusPillClass' => $this->statusPillClass,
            'statusIconClass' => $this->statusIconClass,
            'cancellationReason' => $this->cancellationReason,
            'paymentDisplay' => $this->paymentDisplay,
            'sourceDisplay' => $this->sourceDisplay,
        ];
    }
}
