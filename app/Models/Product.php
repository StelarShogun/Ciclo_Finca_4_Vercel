<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * @property-read Category|null $category
 * @property-read Collection<int, Brand> $brands
 */
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

    /** Umbral público (catálogo, detalle, invitado o logueado): por encima no se muestra aviso de “pocas unidades”. */
    public const CLIENT_LOW_STOCK_THRESHOLD = 10;

    /** Respuestas JSON cortas para carrito y checkout (cliente). */
    public const MSG_CLIENT_AGOTADO = 'Producto agotado';

    public const MSG_CLIENT_STOCK_INSUFICIENTE = 'Stock insuficiente';

    /**
     * Normaliza estado (inglés o legacy español) al ENUM canónico en inglés.
     */
    public static function canonicalStatus(?string $raw): string
    {
        $s = strtolower(trim((string) $raw));

        return match ($s) {
            'activo' => 'active',
            'inactivo' => 'inactive',
            'agotado' => 'out_of_stock',
            'descontinuado' => 'discontinued',
            'active', 'inactive', 'out_of_stock', 'discontinued' => $s,
            default => $s,
        };
    }

    /** Estado lógico para reglas de tienda (etiquetas, carrito). */
    public function effectiveStatus(): string
    {
        return self::canonicalStatus($this->attributes['status'] ?? null);
    }

    /** Destacados en inicio: solo “activo” (inglés o español). */
    public function scopeActiveInClientStore(Builder $query): Builder
    {
        $ok = ['active', 'activo'];
        $placeholders = implode(',', array_fill(0, count($ok), '?'));

        return $query->whereRaw(
            'LOWER(TRIM(COALESCE(status, \'\'))) IN ('.$placeholders.')',
            $ok
        );
    }

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

    /**
     * Etiqueta corta para listados del cliente (catálogo, home, relacionados).
     * La cantidad numérica se muestra en las vistas cuando {@see isPurchasableByClient()} es true (CF4-62).
     * “Quedan pocas unidades” solo si hay stock pero no supera {@see self::CLIENT_LOW_STOCK_THRESHOLD}.
     */
    public function clientCatalogStockLabel(): string
    {
        $st = $this->effectiveStatus();

        if ($this->stock_current <= 0 || $st === 'out_of_stock') {
            return 'Agotado';
        }

        if ($st !== 'active') {
            return 'No disponible';
        }

        if ($this->stock_current <= self::CLIENT_LOW_STOCK_THRESHOLD) {
            return 'Quedan pocas unidades';
        }

        return 'Disponible';
    }

    /**
     * Disponibilidad para inventario admin (misma lógica de umbrales que la tienda; incluye inactivo/descontinuado).
     */
    public function adminAvailabilityLabel(): string
    {
        $st = $this->effectiveStatus();

        if (in_array($st, ['inactive', 'discontinued'], true)) {
            return 'No disponible';
        }

        if ($st === 'out_of_stock' || $this->stock_current <= 0) {
            return 'Agotado';
        }

        if ($st === 'active' && $this->stock_current > 0) {
            if ($this->stock_current <= self::CLIENT_LOW_STOCK_THRESHOLD) {
                return 'Quedan pocas unidades';
            }

            return 'Disponible';
        }

        return 'No disponible';
    }

    /** Invitado o cliente: aviso “pocas unidades” (misma regla que {@see clientCatalogStockLabel()} para stock > 0). */
    public function clientShowsLowStockWarning(): bool
    {
        return $this->effectiveStatus() === 'active'
            && $this->stock_current > 0
            && $this->stock_current <= self::CLIENT_LOW_STOCK_THRESHOLD;
    }

    /** Puede añadirse al carrito desde la tienda (activo y con existencias). */
    public function isPurchasableByClient(): bool
    {
        return $this->effectiveStatus() === 'active' && $this->stock_current > 0;
    }

    /** Fragmento de URL legible para SEO (nombre del producto). */
    public function clientPublicSlug(): string
    {
        $s = Str::slug((string) ($this->name ?? 'producto'));

        return $s !== '' ? $s : 'producto';
    }

    /** URL canónica de la ficha en la tienda pública. */
    public function clientProductUrl(): string
    {
        return route('clients.product', [
            'id' => $this->product_id,
            'slug' => $this->clientPublicSlug(),
        ]);
    }

    /**
     * Productos activos con existencia pero en o por debajo del umbral (1…{@see CLIENT_LOW_STOCK_THRESHOLD}).
     * Excluye agotados (0) para alinear KPI admin con la tienda pública.
     */
    public function scopeLowStockAlert(Builder $query): Builder
    {
        return $query->activeInClientStore()
            ->where('stock_current', '>', 0)
            ->where('stock_current', '<=', self::CLIENT_LOW_STOCK_THRESHOLD);
    }

    // Validation rules to ensure data integrity when saving products, with specific checks for active products and price consistency
    protected static function booted(): void
    {
        static::saving(function ($p) {
            if ($p->effectiveStatus() === 'active') {
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
