<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 */
class Brand extends Model
{
    protected $table = 'brands';

    protected $fillable = ['name'];

    public $timestamps = false;

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'products_brand', 'brand_id', 'product_id', 'id', 'product_id');
    }
}
