<?php

namespace App\DTOs\Admin\Sales;

final readonly class AdminSaleData
{
    /**
     * @param  list<SaleItemData>  $items
     */
    public function __construct(
        public ?string $buyerName,
        public ?string $buyerEmail,
        public ?int $clientId,
        public string $paymentMethod,
        public ?string $paymentReference,
        public float $discount,
        public float $ivaPercentage,
        public ?string $notes,
        public array $items,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            buyerName: self::nullableString($data['buyer_name'] ?? null),
            buyerEmail: self::nullableString($data['buyer_email'] ?? null),
            clientId: isset($data['client_id']) ? (int) $data['client_id'] : null,
            paymentMethod: (string) $data['payment_method'],
            paymentReference: self::nullableString($data['payment_reference'] ?? null),
            discount: round(max(0, (float) ($data['discount'] ?? 0)), 2),
            ivaPercentage: round(max(0, min(13, (float) ($data['iva_percentage'] ?? 0))), 2),
            notes: self::nullableString($data['notes'] ?? null),
            items: collect($data['items'] ?? [])->map(fn (array $item) => SaleItemData::fromArray($item))->values()->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'buyer_name' => $this->buyerName,
            'buyer_email' => $this->buyerEmail,
            'client_id' => $this->clientId,
            'payment_method' => $this->paymentMethod,
            'payment_reference' => $this->paymentReference,
            'discount' => $this->discount,
            'iva_percentage' => $this->ivaPercentage,
            'notes' => $this->notes,
            'items' => array_map(fn (SaleItemData $item) => $item->toArray(), $this->items),
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === null || $value === '' ? null : (string) $value;
    }
}
