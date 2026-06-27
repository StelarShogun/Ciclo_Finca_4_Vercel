<?php

namespace App\Http\Resources\Admin;

use App\Enums\Inventory\MovementType;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var InventoryMovement $movement */
        $movement = $this->resource;

        return [
            'id' => $movement->id,
            'type' => $movement->type instanceof MovementType
                ? $movement->type->value
                : $movement->type,
            'type_label' => $movement->typeLabel(),
            'type_badge' => $movement->typeBadgeClass(),
            'origin' => $movement->origin,
            'origin_label' => $movement->originLabel(),
            'quantity' => $movement->quantity,
            'stock_before' => $movement->stock_before,
            'stock_after' => $movement->stock_after,
            'reference_id' => $movement->reference_id,
            'reason' => $movement->reason,
            'admin' => $movement->adminUser ? [
                'id' => $movement->adminUser->user_id,
                'name' => $movement->adminName(),
            ] : null,
            'created_at' => $movement->created_at?->toISOString(),
            'created_at_human' => $movement->created_at?->format('d/m/Y H:i:s'),
        ];
    }
}
