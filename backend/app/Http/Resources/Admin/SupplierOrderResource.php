<?php

namespace App\Http\Resources\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
final class SupplierOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'num_order' => (int) $this->num_order,
            'po_number' => $this->po_number ?: '#'.$this->num_order,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'supplier_id' => (int) $this->supplier->supplier_id,
                'name' => $this->supplier->name,
                'primary_contact' => $this->supplier->primary_contact,
                'email' => $this->supplier->email,
                'phone' => $this->supplier->phone,
            ]),
            'state' => $this->state,
            'state_label' => Order::STATE_LABELS[$this->state] ?? ucfirst((string) $this->state),
            'date' => $this->date?->format('d/m/Y H:i'),
            'estimated_delivery_date' => $this->estimated_delivery_date?->format('d/m/Y'),
            'received_at' => $this->received_at?->format('d/m/Y H:i'),
            'closed_with_shorts' => (bool) $this->closed_with_shorts,
            'total' => (float) $this->total,
            'items' => $this->whenLoaded('orderItems', fn () => $this->orderItems->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
                'quantity' => (int) $item->quantity,
                'received_quantity' => $item->received_quantity !== null ? (int) $item->received_quantity : null,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ])->values()->all()),
        ];
    }
}
