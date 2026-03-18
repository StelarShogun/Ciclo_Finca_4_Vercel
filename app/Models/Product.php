<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'category_id','supplier_id','name','description','image','images',
        'sale_price','purchase_price','stock_current','stock_minimum','status'
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock_current' => 'integer',
        'stock_minimum' => 'integer',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Devuelve las rutas de imagen a mostrar (imagen principal + adicionales para carrusel).
     */
    public function getDisplayImages(): array
    {
        $main = $this->image ?? 'default.png';
        $extra = $this->images ?? [];
        $all = array_merge([$main], is_array($extra) ? array_values($extra) : []);
        return array_filter($all) ?: ['default.png'];
    }

    public function category() { return $this->belongsTo(Category::class, 'category_id','category_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id','supplier_id'); }
    
    // Sales module (English)
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'product_id', 'product_id');
    }

    protected static function booted(): void {
        static::saving(function ($p) {
            if ($p->status === 'active') {
                if ($p->stock_minimum < 1) {
                    throw ValidationException::withMessages([
                        'stock_minimum' => 'El stock mínimo debe ser ≥ 1 para productos activos.'
                    ]);
                }
                if ($p->stock_current < $p->stock_minimum) {
                    throw ValidationException::withMessages([
                        'stock_current' => 'El stock actual no puede ser menor que el stock mínimo.'
                    ]);
                }
            }
            if ($p->sale_price < $p->purchase_price) {
                throw ValidationException::withMessages([
                    'sale_price' => 'El precio de venta no puede ser menor que el de compra.'
                ]);
            }
        });
    }
}
