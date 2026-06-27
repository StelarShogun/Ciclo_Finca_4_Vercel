<?php

namespace App\Services\Admin\Classifications;

use App\Models\Category;
use App\Models\ClassificationValue;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CF4-84 — Asigna valores (por atributo) a cada producto; un valor por atributo, por subcategoría.
 */
class ProductClassificationAssignmentService
{
    /**
     * Validates that each value belongs to a dimension of the given category, dimensions are not repeated,
     * and rows are not soft-deleted. Does not require the category to be a subcategory (caller checks).
     *
     * @param  list<int>  $valueIds
     *
     * @throws ValidationException
     */
    public function assertValuesValidForCategory(int $categoryId, array $valueIds): void
    {
        $valueIds = array_values(array_unique(array_map('intval', $valueIds)));
        if ($valueIds === []) {
            return;
        }

        $values = ClassificationValue::query()
            ->whereIn('id', $valueIds)
            ->whereNull('deleted_at')
            ->with(['dimension' => fn ($q) => $q->whereNull('deleted_at')])
            ->get();

        if ($values->count() !== count($valueIds)) {
            throw ValidationException::withMessages([
                'classification_value_ids' => ['Una o más opciones no existen o ya no están disponibles.'],
            ]);
        }

        $dimensionIds = [];
        foreach ($values as $value) {
            $dimension = $value->dimension;
            if ($dimension === null || (int) $dimension->category_id !== $categoryId) {
                throw ValidationException::withMessages([
                    'classification_value_ids' => ['Las opciones deben coincidir con el tipo de producto elegido.'],
                ]);
            }
            if (in_array((int) $dimension->id, $dimensionIds, true)) {
                throw ValidationException::withMessages([
                    'classification_value_ids' => ['Solo un valor por cada atributo (por ejemplo un color y una talla).'],
                ]);
            }
            $dimensionIds[] = (int) $dimension->id;
        }
    }

    /**
     * @param  list<int>  $valueIds
     */
    public function syncForProduct(Product $product, array $valueIds): void
    {
        $product->loadMissing('category');
        $category = $product->category;

        if (! $category instanceof Category) {
            $product->classificationValues()->detach();

            return;
        }

        if ($category->parent_category_id === null) {
            if ($valueIds !== []) {
                throw ValidationException::withMessages([
                    'classification_value_ids' => ['Primero el producto debe tener un tipo concreto en el catálogo.'],
                ]);
            }
            $product->classificationValues()->detach();

            return;
        }

        $valueIds = array_values(array_unique(array_map('intval', $valueIds)));

        if ($valueIds === []) {
            DB::transaction(fn () => $product->classificationValues()->detach());

            return;
        }

        $this->assertValuesValidForCategory((int) $product->category_id, $valueIds);

        $values = ClassificationValue::query()
            ->whereIn('id', $valueIds)
            ->whereNull('deleted_at')
            ->with(['dimension' => fn ($q) => $q->whereNull('deleted_at')])
            ->get()
            ->keyBy('id');

        $sync = [];
        foreach ($valueIds as $vid) {
            $value = $values->get($vid);
            if ($value === null || $value->dimension === null) {
                continue;
            }
            $sync[$vid] = ['classification_dimension_id' => $value->dimension->id];
        }

        DB::transaction(function () use ($product, $sync) {
            $product->classificationValues()->sync($sync);
        });
    }
}
