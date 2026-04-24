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
    ];

    protected $casts = [
        'date' => 'datetime',
        'estimated_delivery_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_num_order', 'num_order');
    }

    /** Admin que confirmó el pedido con el proveedor (CF4-15). */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'confirmed_by', 'user_id');
    }
}
