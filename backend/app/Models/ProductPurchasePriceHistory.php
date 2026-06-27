<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Tracks every confirmed change to a product's purchase_price.
 *
 * @property int $id
 * @property int $product_id
 * @property float $previous_price
 * @property float $new_price
 * @property float $difference_amount
 * @property float $difference_percentage
 * @property float $threshold_percentage
 * @property string $source
 * @property string|null $xml_file_name
 * @property string|null $reason
 * @property int|null $changed_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Product|null   $product
 * @property-read AdminUser|null $changedBy
 */
class ProductPurchasePriceHistory extends Model
{
    protected $table = 'product_purchase_price_histories';

    protected $fillable = [
        'product_id',
        'previous_price',
        'new_price',
        'difference_amount',
        'difference_percentage',
        'threshold_percentage',
        'source',
        'xml_file_name',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'previous_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'difference_percentage' => 'decimal:4',
        'threshold_percentage' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'changed_by', 'user_id');
    }
}
