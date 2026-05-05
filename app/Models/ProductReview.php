<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class ProductReview extends Model
{
    protected $table = 'product_reviews';

    protected $primaryKey = 'review_id';

    protected $fillable = [
        'product_id',
        'client_id',
        'stars',
    ];

    protected $casts = [
        'stars' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }

    /** Public ratings shown on the storefront (excludes placeholder rows with null stars). */
    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query->whereNotNull('stars');
    }

    /**
     * Average stars and review count per product (only rows with non-null stars).
     * One review ⇒ avg equals that row’s stars; many reviews ⇒ SQL AVG across all.
     *
     * @param  array<int|string>  $productIds
     * @return array<int, array{avg: float, count: int}>
     */
    public static function aggregatesForProductIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', array_filter($productIds))));
        if ($productIds === [] || ! Schema::hasTable((new static)->getTable())) {
            return [];
        }

        $rows = static::query()
            ->publiclyListed()
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->selectRaw('product_id, AVG(stars) as avg_stars, COUNT(*) as review_count')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $pid = (int) $row->product_id;
            $out[$pid] = [
                'avg' => round((float) $row->avg_stars, 2),
                'count' => (int) $row->review_count,
            ];
        }

        return $out;
    }
}
