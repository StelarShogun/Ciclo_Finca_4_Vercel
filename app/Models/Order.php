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
        'delivered_at',
    ];

    protected $casts = [
        'date' => 'datetime',
        'estimated_delivery_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_num_order', 'num_order');
    }
}
