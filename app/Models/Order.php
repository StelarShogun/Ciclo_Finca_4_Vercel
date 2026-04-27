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
        'received_at',   // Usado por receiveOrder() — recepción granular con cantidades por línea.
        'delivered_at',  // Usado por updateState('delivered') — transición directa vía InventoryMovementService.
    ];

    protected $casts = [
        'date'                    => 'datetime',
        'estimated_delivery_date' => 'date',
        'received_at'             => 'datetime',
        'delivered_at'            => 'datetime',
        'confirmed_at'            => 'datetime',
    ];

    /**
     * Valid state transitions.
     *
     * El flujo principal es: draft → confirmed → delivered / cancelled.
     * El estado "pending" se conserva únicamente como origen para compatibilidad
     * con órdenes históricas que aún lo tengan asignado.
     */
    public const TRANSITIONS = [
        'draft'     => ['confirmed', 'cancelled'],
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['delivered', 'cancelled'],
    ];

    public const STATE_LABELS = [
        'draft'     => 'Borrador',
        'pending'   => 'Pendiente',
        'confirmed' => 'Confirmado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
    ];

    public function canTransitionTo(string $newState): bool
    {
        return in_array($newState, self::TRANSITIONS[$this->state] ?? [], true);
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