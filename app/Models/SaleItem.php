<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'producto_id',
        'quantity',
        'unit_price',
        'unit_discount',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'producto_id');
    }

    public function scopeBySale($query, $saleId)
    {
        return $query->where('sale_id', $saleId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('producto_id', $productId);
    }

    public function getFinalPriceAttribute()
    {
        return $this->unit_price - $this->unit_discount;
    }
}
