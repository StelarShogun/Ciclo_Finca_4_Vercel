<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property-read Category|null $category
 * @property-read Collection<int, Brand> $brands
 * @property-read Collection<int, ClassificationValue> $classificationValues
 */
class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'products';

    protected $primaryKey = 'product_id';

    /** Route parameter `{product}` resolves by `product_id` (not `id`). */
    public function getRouteKeyName(): string
    {
        return 'product_id';
    }

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

    public const MSG_CLIENT_AGOTADO = 'Producto agotado';

    public const MSG_CLIENT_STOCK_INSUFICIENTE = 'Stock insuficiente';

    /**
     * SKU de catálogo derivado del ID (no existe columna dedicada).
     * Debe coincidir con SQL: CONCAT('BK-', LPAD(product_id, 3, '0')).
     */
    public static function skuFromId(int $productId): string
    {
        return 'BK-'.str_pad((string) $productId, 3, '0', STR_PAD_LEFT);
    }

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

    public function effectiveStatus(): string
    {
        return self::canonicalStatus($this->attributes['status'] ?? null);
    }

    public function scopeActiveInClientStore(Builder $query): Builder
    {
        $ok = ['active', 'activo'];
        $placeholders = implode(',', array_fill(0, count($ok), '?'));

        return $query->whereRaw(
            'LOWER(TRIM(COALESCE(status, \'\'))) IN ('.$placeholders.')',
            $ok
        );
    }

    public function registerMediaCollections(): void
    {
        $disk = config('media-library.disk_name', 'public');

        $this->addMediaCollection('main_image')
             ->useDisk($disk)
             ->singleFile();

        $this->addMediaCollection('gallery')
             ->useDisk($disk);
    }

    public function getDisplayImages(): array
    {
        $main = $this->image ?? 'default.png';
        $extra = $this->images ?? [];
        $all = array_merge([$main], is_array($extra) ? array_values($extra) : []);

        return array_filter($all) ?: ['default.png'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id', 'product_id');
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'products_brand', 'product_id', 'brand_id', 'product_id', 'id');
    }

    /** CF4-84: Assigned classification values (one row per dimension per product via pivot). */
    public function classificationValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassificationValue::class,
            'classification_product',
            'product_id',
            'classification_value_id',
            'product_id',
            'id',
        )->withPivot('classification_dimension_id');
    }

    /** CF4-62 / CF4-50: Etiqueta corta para listados del cliente (umbral = stock mínimo por producto). */
    public function clientCatalogStockLabel(): string
    {
        $st = $this->effectiveStatus();

        if ($this->stock_current <= 0 || $st === 'out_of_stock') {
            return 'Agotado';
        }

        if ($st !== 'active') {
            return 'No disponible';
        }

        if ((int) $this->stock_minimum > 0 && $this->stock_current <= (int) $this->stock_minimum) {
            return 'Quedan pocas unidades';
        }

        return 'Disponible';
    }

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
            if ((int) $this->stock_minimum > 0 && $this->stock_current <= (int) $this->stock_minimum) {
                return 'Quedan pocas unidades';
            }

            return 'Disponible';
        }

        return 'No disponible';
    }

    public function clientShowsLowStockWarning(): bool
    {
        return $this->effectiveStatus() === 'active'
            && $this->stock_current > 0
            && (int) $this->stock_minimum > 0
            && $this->stock_current <= (int) $this->stock_minimum;
    }

    public function isPurchasableByClient(): bool
    {
        return $this->effectiveStatus() === 'active' && $this->stock_current > 0;
    }

    public function clientPublicSlug(): string
    {
        $s = Str::slug((string) ($this->name ?? 'producto'));

        return $s !== '' ? $s : 'producto';
    }

    public function clientProductUrl(): string
    {
        return route('clients.product', [
            'id' => $this->product_id,
            'slug' => $this->clientPublicSlug(),
        ]);
    }

    /** CF4-50: Stock por encima de cero pero en o por debajo del mínimo configurado (si el mínimo es 0, no alerta). */
    public function scopeLowStockAlert(Builder $query): Builder
    {
        return $query->activeInClientStore()
            ->where('stock_minimum', '>', 0)
            ->where('stock_current', '>', 0)
            ->whereColumn('stock_current', '<=', 'stock_minimum');
    }

    /** Badge en inventario admin: danger = sin stock, warning = en o bajo el mínimo, success = por encima del mínimo. */
    public function adminInventoryStockBadgeClass(): string
    {
        if ($this->stock_current <= 0) {
            return 'danger';
        }

        if ((int) $this->stock_minimum > 0 && $this->stock_current <= (int) $this->stock_minimum) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * Para exportes PDF: 'high' = stock OK, 'medium' = bajo respecto al mínimo, 'low' = agotado.
     */
    public static function adminStockExportTier(int $stockCurrent, int $stockMinimum): string
    {
        if ($stockCurrent <= 0) {
            return 'low';
        }
        if ($stockMinimum > 0 && $stockCurrent <= $stockMinimum) {
            return 'medium';
        }

        return 'high';
    }

    protected static function booted(): void
    {
        static::saving(function ($p) {
            if ($p->effectiveStatus() === 'active') {
                if ((int) $p->stock_minimum < 0) {
                    throw ValidationException::withMessages([
                        'stock_minimum' => 'El stock mínimo no puede ser negativo.',
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
