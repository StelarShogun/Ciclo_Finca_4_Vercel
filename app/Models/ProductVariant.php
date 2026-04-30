<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'base_product_id',
        'variant_product_id',
    ];

    protected $casts = [
        'base_product_id' => 'integer',
        'variant_product_id' => 'integer',
    ];

    public function baseProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'base_product_id', 'product_id');
    }

    public function variantProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_product_id', 'product_id');
    }
}

