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
        'total',
        'received_at',
    ];

    protected $casts = [
        'date'                    => 'datetime',
        'estimated_delivery_date' => 'date',
        'received_at'             => 'datetime',
    ];

    /**
     * Valid state transitions.
     * draft → pending → confirmed → delivered
     *                             → cancelled (desde draft, pending, confirmed)
     */
    public const TRANSITIONS = [
        'draft'     => ['pending', 'cancelled'],
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
}