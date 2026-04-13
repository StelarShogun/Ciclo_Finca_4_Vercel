<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read ClassificationDimension $dimension
 */
class ClassificationValue extends Model
{
    use SoftDeletes;

    protected $table = 'classification_values';

    protected $fillable = [
        'classification_dimension_id',
        'value',
        'normalized_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(ClassificationDimension::class, 'classification_dimension_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'classification_product',
            'classification_value_id',
            'product_id',
            'id',
            'product_id',
        )->withPivot('classification_dimension_id');
    }

    /** Normalize for duplicate checks (same meaning, different casing/spacing). */
    public static function normalizeStoredValue(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
