<?php

namespace App\DTOs\Admin\SupplierOrders;

final readonly class SupplierOrderData
{
    /**
     * @param  list<SupplierOrderItemData>  $items
     */
    public function __construct(
        public int $supplierId,
        public array $items,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            supplierId: (int) $data['supplier_id'],
            items: collect($data['items'] ?? [])->map(fn (array $item) => SupplierOrderItemData::fromArray($item))->values()->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'supplier_id' => $this->supplierId,
            'items' => array_map(fn (SupplierOrderItemData $item) => $item->toArray(), $this->items),
        ];
    }
}
