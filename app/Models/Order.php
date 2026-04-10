<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $primaryKey = 'num_order';

    protected $fillable = ['supplier_id', 'products', 'date', 'state', 'total'];

    protected $casts = [
        'products' => 'array',
        'date'     => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }
}
