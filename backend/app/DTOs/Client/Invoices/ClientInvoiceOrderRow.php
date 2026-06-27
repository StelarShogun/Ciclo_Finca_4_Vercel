<?php

namespace App\DTOs\Client\Invoices;

final readonly class ClientInvoiceOrderRow
{
    public function __construct(
        public int $id,
        public ?string $invoiceNumber,
        public string $saleDateLabel,
        public string $statusLabel,
        public string $statusTone,
        public string $totalFormatted,
        public string $showUrl,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoiceNumber' => $this->invoiceNumber,
            'saleDateLabel' => $this->saleDateLabel,
            'statusLabel' => $this->statusLabel,
            'statusTone' => $this->statusTone,
            'totalFormatted' => $this->totalFormatted,
            'showUrl' => $this->showUrl,
        ];
    }
}
