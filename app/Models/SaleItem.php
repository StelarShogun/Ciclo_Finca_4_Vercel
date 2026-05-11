<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|string $id
 * @property int $sale_id
 * @property int $product_id
 * @property int $quantity
 * @property numeric-string|null $unit_price
 * @property numeric-string|null $total
 * @property float|int|string|null $percentage
 * @property float|int|string|null $total_revenue
 * @property string|null $category_name
 * @property-read Product|null $product
 */
class SaleItem extends Model
{
    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
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
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function scopeBySale($query, $saleId)
    {
        return $query->where('sale_id', $saleId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function getFinalPriceAttribute()
    {
        return $this->unit_price - $this->unit_discount;
    }
}
