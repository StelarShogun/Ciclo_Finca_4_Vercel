<?php

namespace App\Services\Admin\Products;

use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class InventoryClassificationFilterService
{
    public function __construct(
        private AdminInventoryProductQuery $inventoryProductQuery,
    ) {}

    /**
     * @return array<int, array{slug: string, label: string, options: array<int, array{value: string, label: string}>}>
     */
    public function filtersForRequest(?Request $request = null): array
    {
        $requestForScope = $request ?? new Request;
        $requestWithoutClassification = $requestForScope->duplicate();
        $requestWithoutClassification->merge(['classifications' => []]);
        $filteredProductIds = $this->inventoryProductQuery->filteredQuery($requestWithoutClassification)
            ->reorder()
            ->select('products.product_id');

        $dimensions = ClassificationDimension::query()
            ->select(['slug', 'label'])
            ->join('classification_product', 'classification_product.classification_dimension_id', '=', 'classification_dimensions.id')
            ->joinSub(clone $filteredProductIds, 'inventory_filtered_products', function ($join) {
                $join->on('inventory_filtered_products.product_id', '=', 'classification_product.product_id');
            })
            ->whereNull('classification_dimensions.deleted_at')
            ->groupBy('classification_dimensions.slug', 'classification_dimensions.label')
            ->orderBy('label')
            ->get();

        return $dimensions->map(function (ClassificationDimension $dimension) use ($filteredProductIds) {
            return [
                'slug' => (string) $dimension->slug,
                'label' => (string) $dimension->label,
                'options' => $this->valuesBySlug((string) $dimension->slug, clone $filteredProductIds),
            ];
        })->filter(fn (array $f) => $f['options'] !== [])->values()->all();
    }

    /**
     * @return array<int, array{slug: string, dimension_label: string, value: string, value_label: string}>
     */
    public function activeFilters(Request $request): array
    {
        $classifications = collect($request->input('classifications', []))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '');

        if ($classifications->isEmpty()) {
            return [];
        }

        $dimensions = ClassificationDimension::query()
            ->whereIn('slug', $classifications->keys())
            ->pluck('label', 'slug');

        $result = [];
        foreach ($classifications as $slug => $normalizedValue) {
            $slug = (string) $slug;
            $normalizedValue = (string) $normalizedValue;

            $displayValue = ClassificationValue::query()
                ->selectRaw('MIN(classification_values.value) AS display_value')
                ->join('classification_dimensions', 'classification_dimensions.id', '=', 'classification_values.classification_dimension_id')
                ->where('classification_dimensions.slug', $slug)
                ->where('classification_values.normalized_value', $normalizedValue)
                ->whereNull('classification_dimensions.deleted_at')
                ->whereNull('classification_values.deleted_at')
                ->value('display_value');

            $result[] = [
                'slug' => $slug,
                'dimension_label' => (string) ($dimensions[$slug] ?? $slug),
                'value' => $normalizedValue,
                'value_label' => (string) ($displayValue ?? $normalizedValue),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function suggest(Request $request, string $slug): array
    {
        $requestForScope = $request->duplicate();
        $classifications = (array) $request->input('classifications', []);
        unset($classifications[$slug]);
        $requestForScope->merge(['classifications' => $classifications]);

        $filteredProductIds = $this->inventoryProductQuery->filteredQuery($requestForScope)
            ->reorder()
            ->select('products.product_id');

        $search = trim((string) $request->get('q', ''));
        $limit = max(1, min(50, (int) $request->get('limit', 50)));

        return $this->valuesBySlug($slug, clone $filteredProductIds, $search, $limit);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function valuesBySlug(
        string $slug,
        ?Builder $filteredProductIds = null,
        ?string $search = null,
        ?int $limit = null
    ): array {
        $query = ClassificationValue::query()
            ->selectRaw('classification_values.normalized_value, MIN(classification_values.value) AS display_value')
            ->join('classification_dimensions', 'classification_dimensions.id', '=', 'classification_values.classification_dimension_id')
            ->join('classification_product', 'classification_product.classification_value_id', '=', 'classification_values.id')
            ->join('products', 'products.product_id', '=', 'classification_product.product_id')
            ->where('classification_dimensions.slug', $slug)
            ->whereNull('classification_dimensions.deleted_at')
            ->whereNull('classification_values.deleted_at')
            ->groupBy('classification_values.normalized_value')
            ->orderBy('display_value');

        if ($filteredProductIds !== null) {
            $query->joinSub($filteredProductIds, 'inventory_filtered_products', function ($join) {
                $join->on('inventory_filtered_products.product_id', '=', 'classification_product.product_id');
            });
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%'.addcslashes(trim($search), '%_\\').'%';
            $query->where('classification_values.value', 'LIKE', $term);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn ($row) => [
                'value' => (string) $row->normalized_value,
                'label' => (string) $row->display_value,
            ])
            ->values()
            ->all();
    }
}
