<?php

namespace App\Http\Resources\Admin;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sale */
final class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sale_id' => $this->sale_id,
            'invoice_number' => $this->invoice_number,
            'sale_date' => $this->sale_date->toISOString(),
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'subtotal' => $this->subtotal,
            'iva' => $this->iva,
            'discount' => $this->discount,
            'total' => $this->total,
            'notes' => $this->notes,
            'order_source' => $this->order_source,
            'days_remaining_until_expiration' => $this->days_remaining_until_expiration,
            'expires_at' => $this->expires_at->toISOString(),
            'is_expiry_warning' => $this->is_expiry_warning,
            'ready_at' => $this->ready_at?->toISOString(),
            'confirmed_at' => $this->status === 'completed'
                ? $this->updated_at?->toISOString()
                : null,
            'order_placed_at_label' => $this->adminOrderPlacedAtLabel(),
            'ready_at_label' => $this->adminReadyAtLabel(),
            'confirmed_at_label' => $this->adminConfirmedAtLabel(),
            'sale_date_label' => $this->adminSaleDateLabel(),
            'pickup_expires_at' => $this->pickup_expires_at?->toISOString(),
            'pickup_time_remaining_label' => $this->pickup_time_remaining_label,
            'is_pickup_expired' => $this->isPickupExpired(),
            'buyer' => [
                'name' => $this->buyer_name,
                'email' => $this->buyer_email,
            ],
            'client' => $this->client ? [
                'user_id' => $this->client->user_id,
                'name' => $this->client->name,
                'first_surname' => $this->client->first_surname,
                'second_surname' => $this->client->second_surname,
                'gmail' => $this->client->gmail,
            ] : null,
            'sale_items' => $this->saleItems->map(fn (SaleItem $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'product' => $item->product ? [
                    'product_id' => $item->product->product_id,
                    'name' => $item->product->name,
                    'sku' => $item->product->displaySku(),
                ] : null,
            ]),
        ];
    }
}
