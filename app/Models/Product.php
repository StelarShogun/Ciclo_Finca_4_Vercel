<?php

namespace App\Models;

use App\Services\Admin\ProductCatalog\CatalogImportState;
use App\Support\GdImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property-read Category|null $category
 * @property-read Supplier|null $supplier
 * @property-read Collection<int, Brand> $brands
 * @property-read Collection<int, Product> $variants
 * @property-read Collection<int, ClassificationValue> $classificationValues
 */
// Product model with media support and inventory-related helpers.
class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'products';

    protected $primaryKey = 'product_id';

    // Resolves route model binding using product_id.
    public function getRouteKeyName(): string
    {
        return 'product_id';
    }

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    // Mass-assignable product attributes.
    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'description',
        'image',
        'images',
        'sale_price',
        'purchase_price',
        'stock_current',
        'stock_minimum',
        'status',
        'is_featured',
    ];

    // Casts numeric, boolean, array, and datetime attributes.
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

    // Builds the catalog SKU from the product ID.
    public static function skuFromId(int $productId): string
    {
        return 'BK-'.str_pad((string) $productId, 3, '0', STR_PAD_LEFT);
    }

    // Returns a custom SKU or a generated BK-xxx code.
    public function displaySku(): string
    {
        $custom = trim((string) ($this->attributes['sku'] ?? ''));

        return $custom !== '' ? $custom : self::skuFromId((int) $this->product_id);
    }

    // Normalizes localized and canonical status values.
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

    // Returns the normalized product status.
    public function effectiveStatus(): string
    {
        return self::canonicalStatus($this->attributes['status'] ?? null);
    }

    // Limits the query to products visible in the client store.
    public function scopeActiveInClientStore(Builder $query): Builder
    {
        $ok = ['active', 'activo'];
        $placeholders = implode(',', array_fill(0, count($ok), '?'));

        return $query->whereRaw(
            'LOWER(TRIM(COALESCE(status, \'\'))) IN ('.$placeholders.')',
            $ok
        );
    }

    // Registers media collections for the main image and gallery.
    public function registerMediaCollections(): void
    {
        $disk = config('media-library.disk_name', 'public');

        $this->addMediaCollection('main_image')
            ->useDisk($disk)
            ->singleFile();

        $this->addMediaCollection('gallery')
            ->useDisk($disk);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if (! GdImage::supportsWebp() || CatalogImportState::isFastImport()) {
            return;
        }

        $this
            ->addMediaConversion('webp_96')
            ->performOnCollections('main_image', 'gallery')
            ->format('webp')
            ->width(96)
            ->quality(70)
            ->nonQueued();

        $this
            ->addMediaConversion('webp_480')
            ->performOnCollections('main_image', 'gallery')
            ->format('webp')
            ->width(480)
            ->quality(76)
            ->nonQueued();

        $this
            ->addMediaConversion('webp_768')
            ->performOnCollections('main_image', 'gallery')
            ->format('webp')
            ->width(768)
            ->quality(78)
            ->nonQueued();

        $this
            ->addMediaConversion('webp_1200')
            ->performOnCollections('main_image', 'gallery')
            ->format('webp')
            ->width(1200)
            ->quality(80)
            ->nonQueued();

        $this
            ->addMediaConversion('webp_1920')
            ->performOnCollections('main_image', 'gallery')
            ->format('webp')
            ->width(1920)
            ->quality(80)
            ->nonQueued();
    }

    // Returns the main and extra images, falling back to the default asset.
    public function getDisplayImages(): array
    {
        $main = $this->image ?? 'default.png';
        $extra = $this->images ?? [];
        $all = array_merge([$main], is_array($extra) ? array_values($extra) : []);

        return array_filter($all) ?: ['default.png'];
    }

    // Product category relationship.
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    // Product supplier relationship.
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    // Sale items associated with this product.
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id', 'product_id');
    }

    // Reviews submitted by clients who bought this product.
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'product_id', 'product_id');
    }

    // Brands associated with this product.
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'products_brand', 'product_id', 'brand_id', 'product_id', 'id');
    }

    // Classification values assigned through the pivot table.
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

    // Returns the stock label shown in client listings (CF4-129: fixed tiers by quantity).
    public function clientCatalogStockLabel(): string
    {
        $st = $this->effectiveStatus();

        if ($this->stock_current <= 0 || $st === 'out_of_stock') {
            return 'Agotado';
        }

        if ($st !== 'active') {
            return 'No disponible';
        }

        if ($this->stock_current > 5) {
            return 'En stock';
        }

        return 'Últimas unidades';
    }

    // SKU typed in admin only (no auto-generated BK-xxx fallback for storefront cards).
    public function clientCatalogAssignedSku(): ?string
    {
        $s = trim((string) ($this->attributes['sku'] ?? ''));

        return $s !== '' ? $s : null;
    }

    // Purchase price change history for this product.
    public function purchasePriceHistories(): HasMany
    {
        return $this->hasMany(ProductPurchasePriceHistory::class, 'product_id', 'product_id')
            ->orderBy('created_at', 'desc');
    }

    // Returns the availability label used in the admin panel.
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

    // Indicates whether the client UI should show a low-stock warning (CF4-129: 1–5 units).
    public function clientShowsLowStockWarning(): bool
    {
        return $this->effectiveStatus() === 'active'
            && $this->stock_current > 0
            && $this->stock_current <= 5;
    }

    // Indicates whether the product can be purchased by the client.
    public function isPurchasableByClient(): bool
    {
        return $this->effectiveStatus() === 'active' && $this->stock_current > 0;
    }

    // Builds the public slug used in client routes.
    public function clientPublicSlug(): string
    {
        $s = Str::slug((string) ($this->name ?? 'producto'));

        return $s !== '' ? $s : 'producto';
    }

    // Returns the public product URL for the client storefront.
    public function clientProductUrl(): string
    {
        return route('clients.product', [
            'id' => $this->product_id,
            'slug' => $this->clientPublicSlug(),
        ]);
    }

    // Limits the query to products at or below the minimum stock threshold.
    public function scopeLowStockAlert(Builder $query): Builder
    {
        return $query->activeInClientStore()
            ->where('stock_minimum', '>', 0)
            ->where('stock_current', '>', 0)
            ->whereColumn('stock_current', '<=', 'stock_minimum');
    }

    // Returns the admin badge class based on current stock.
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

    // Human-readable low-stock severity for the admin dashboard table (replaces % badges).
    public function adminDashboardLowStockStatusLabel(): string
    {
        if ($this->stock_current <= 0) {
            return 'Sin stock';
        }

        $minimum = (int) $this->stock_minimum;
        if ($minimum <= 0) {
            return 'Stock bajo';
        }

        $pct = self::adminDashboardLowStockPercentOfMinimum(
            (int) $this->stock_current,
            $minimum,
        );

        return match (true) {
            $pct <= 25 => 'Crítico',
            $pct <= 50 => 'Muy bajo',
            default => 'Stock bajo',
        };
    }

    // Badge tone for dashboard low-stock status labels.
    public function adminDashboardLowStockStatusBadgeClass(): string
    {
        if ($this->stock_current <= 0) {
            return 'danger';
        }

        $minimum = (int) $this->stock_minimum;
        if ($minimum <= 0) {
            return 'warning';
        }

        $pct = self::adminDashboardLowStockPercentOfMinimum(
            (int) $this->stock_current,
            $minimum,
        );

        return $pct <= 50 ? 'danger' : 'warning';
    }

    // Tooltip for dashboard low-stock status (keeps % detail for admins).
    public function adminDashboardLowStockStatusTitle(): string
    {
        if ($this->stock_current <= 0) {
            return 'Sin stock disponible';
        }

        $minimum = (int) $this->stock_minimum;
        if ($minimum <= 0) {
            return 'Stock por debajo del umbral configurado';
        }

        $pct = self::adminDashboardLowStockPercentOfMinimum(
            (int) $this->stock_current,
            $minimum,
        );

        return sprintf(
            '%s: %d%% del mínimo requerido (%d / %d)',
            $this->adminDashboardLowStockStatusLabel(),
            $pct,
            (int) $this->stock_current,
            $minimum,
        );
    }

    // Percent of minimum stock (0 when minimum is not configured).
    public static function adminDashboardLowStockPercentOfMinimum(int $stockCurrent, int $stockMinimum): int
    {
        if ($stockMinimum <= 0) {
            return 0;
        }

        return (int) round(($stockCurrent / $stockMinimum) * 100);
    }

    // Maps stock values to export tiers used in PDF reports.
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

    // Validates stock and pricing rules before saving the model.
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

    // Inventory movement history for this product.
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_id', 'product_id')
            ->orderBy('created_at', 'asc');
    }

    // Variant links where this product is the base product.
    public function variantLinks(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'base_product_id', 'product_id');
    }

    // Product variants associated through the variant link table.
    public function variants(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            ProductVariant::class,
            'base_product_id',
            'product_id',
            'product_id',
            'variant_product_id'
        );
    }
}
