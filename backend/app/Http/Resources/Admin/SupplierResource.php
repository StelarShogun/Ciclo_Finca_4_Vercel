<?php

namespace App\Http\Resources\Admin;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Supplier $supplier */
        $supplier = $this->resource;

        return [
            'supplier_id' => $supplier->supplier_id,
            'name' => $supplier->name,
            'primary_contact' => $supplier->primary_contact,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'address' => $supplier->address,
            'delivery_time' => $supplier->delivery_time,
            'rating' => $supplier->rating !== null ? (float) $supplier->rating : null,
            'status' => 'Activo',
            'created_at' => optional($supplier->created_at)->toIso8601String(),
        ];
    }
}
