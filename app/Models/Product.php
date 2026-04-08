<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $primaryKey = 'product_id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'category_id', 'supplier_id', 'name', 'description', 'image', 'images',
        'sale_price', 'purchase_price', 'stock_current', 'stock_minimum', 'status',
        'is_featured',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock_current' => 'integer',
        'stock_minimum' => 'integer',
        'is_featured' => 'boolean',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Returns an array of image URLs, ensuring the main image is included and handling cases where images may be missing
    public function getDisplayImages(): array
    {
        $main = $this->image ?? 'default.png';
        $extra = $this->images ?? [];
        $all = array_merge([$main], is_array($extra) ? array_values($extra) : []);

        return array_filter($all) ?: ['default.png'];
    }

    // Relationships to other models, allowing easy access to category and supplier information for each product
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    // Sales module relationship
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id', 'product_id');
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'products_brand', 'product_id', 'brand_id', 'product_id', 'id');
    }

    // Validation rules to ensure data integrity when saving products, with specific checks for active products and price consistency
    protected static function booted(): void
    {
        static::saving(function ($p) {
            if ($p->status === 'active') {
                if ($p->stock_minimum < 1) {
                    throw ValidationException::withMessages([
                        'stock_minimum' => 'El stock mínimo debe ser ≥ 1 para productos activos.',
                    ]);
                }
                if ($p->stock_current < $p->stock_minimum) {
                    throw ValidationException::withMessages([
                        'stock_current' => 'El stock actual no puede ser menor que el stock mínimo.',
                    ]);
                }
            }
            if ($p->sale_price < $p->purchase_price) {
                throw ValidationException::withMessages([
                    'sale_price' => 'El precio de venta no puede ser menor que el de compra.',
                ]);
            }
        });
    }
}
