<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class Producto extends Model
{
    use HasFactory;
    
    protected $table = 'productos';
    protected $primaryKey = 'producto_id';
    public $timestamps = true;
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'categoria_id','proveedor_id','nombre','descripcion','imagen',
        'precio_venta','precio_compra','stock_actual','stock_minimo','estado'
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'precio_compra' => 'decimal:2',
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'fecha_creacion' => 'datetime',
        'fecha_modificacion' => 'datetime',
    ];

    public function categoria() { return $this->belongsTo(Categoria::class, 'categoria_id','categoria_id'); }
    public function proveedor() { return $this->belongsTo(Proveedor::class, 'proveedor_id','proveedor_id'); }
    
    // Sales module (English)
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'producto_id', 'producto_id');
    }

    protected static function booted(): void {
        static::saving(function ($p) {
            if ($p->estado === 'activo') {
                if ($p->stock_minimo < 1) {
                    throw ValidationException::withMessages([
                        'stock_minimo' => 'El stock mínimo debe ser ≥ 1 para productos activos.'
                    ]);
                }
                if ($p->stock_actual < $p->stock_minimo) {
                    throw ValidationException::withMessages([
                        'stock_actual' => 'El stock actual no puede ser menor que el stock mínimo.'
                    ]);
                }
            }
            if ($p->precio_venta < $p->precio_compra) {
                throw ValidationException::withMessages([
                    'precio_venta' => 'El precio de venta no puede ser menor que el de compra.'
                ]);
            }
        });
    }
}
