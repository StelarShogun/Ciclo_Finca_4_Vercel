<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Product|null $product
 */
class FavoriteProduct extends Model
{
    protected $table = 'favorite_products';

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'user_id', 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
