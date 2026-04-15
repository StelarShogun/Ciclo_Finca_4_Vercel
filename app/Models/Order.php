<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $primaryKey = 'num_order';

    protected $fillable = [
        'po_number',
        'supplier_id',
        'products',
        'date',
        'estimated_delivery_date',
        'state',
        'total',
    ];

    protected $casts = [
        'products' => 'array',
        'date' => 'datetime',
        'estimated_delivery_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_num_order', 'num_order');
    }
}
