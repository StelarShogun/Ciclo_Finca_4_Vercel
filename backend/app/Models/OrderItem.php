<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Normalized line for a supplier purchase order (`orders`).
 *
 * @property int $id
 * @property int $order_num_order
 * @property int $product_id
 * @property string $name
 * @property int $quantity
 * @property int|null $received_quantity Cantidad efectivamente recibida (null = sin recepción aún)
 * @property numeric-string $unit_price
 * @property numeric-string $total
 * @property-read Product|null $product
 */
class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_num_order',
        'product_id',
        'name',
        'quantity',
        'received_quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_num_order', 'num_order');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
