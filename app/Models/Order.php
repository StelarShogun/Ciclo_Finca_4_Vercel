<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $primaryKey = 'num_order';

    protected $fillable = [
        'supplier_id',
        'po_number',
        'estimated_delivery_date',
        'date',
        'state',
        'confirmed_at',
        'confirmed_by',
        'total',
        'received_at',        // Usado por receiveOrder() — recepción granular con cantidades por línea.
        'delivered_at',       // Usado por updateState('delivered') — transición directa vía InventoryMovementService.
        'closed_with_shorts', // true cuando el pedido se cerró manualmente desde partial_received con faltantes.
    ];

    protected $casts = [
        'date' => 'datetime',
        'estimated_delivery_date' => 'date',
        'received_at' => 'datetime',
        'delivered_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'closed_with_shorts' => 'boolean',
    ];

    /**
     * Transiciones de estado válidas.
     *
     * Flujo principal: draft → confirmed → (recepción vía receiveOrder) → delivered / cancelled.
     * - partial_received → delivered: cierre manual desde receiveOrder cuando todo es completo,
     *   o desde closePartial() cuando el admin decide cerrar aun con faltantes.
     * - "pending" se conserva solo como origen para compatibilidad con pedidos históricos.
     *
     * Nota: "close_partial" NO es un estado; es una acción que transfiere de
     * partial_received a delivered marcando closed_with_shorts = true.
     * Se lista aquí para que canTransitionTo() lo acepte como destino lógico
     * y el controller lo interceda antes de persistir el estado real.
     */
    public const TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['partial_received', 'delivered', 'cancelled'],
        'partial_received' => ['delivered', 'cancelled'],
    ];

    public const STATE_LABELS = [
        'draft' => 'Borrador',
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmado',
        'partial_received' => 'Recepción parcial',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
    ];

    /**
     * Verifica si el pedido puede transicionar al estado dado.
     * "close_partial" se resuelve internamente como "delivered" en el controller;
     * aquí lo mapeamos para que la validación de modelo lo acepte.
     */
    public function canTransitionTo(string $newState): bool
    {
        $target = $newState === 'close_partial' ? 'delivered' : $newState;

        return in_array($target, self::TRANSITIONS[$this->state] ?? [], true);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_num_order', 'num_order');
    }

    public function stateTimeline(): HasMany
    {
        return $this->hasMany(OrderStateTimeline::class, 'num_order', 'num_order')
            ->orderBy('changed_at');
    }

    /** Admin que confirmó el pedido con el proveedor. */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'confirmed_by', 'user_id');
    }
}
