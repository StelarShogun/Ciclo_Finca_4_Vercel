<?php

namespace App\Services\Admin\Classifications;

use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ClassificationCatalogService
{
    private const OPTIONS_CACHE_TTL_SECONDS = 300;

    public function optionsPayload(Category $category): array
    {
        if ($category->parent_category_id === null) {
            return ['attributes' => [], 'dimensions' => []];
        }

        $categoryId = (int) $category->category_id;
        $list = Cache::remember(
            $this->optionsCacheKey($categoryId),
            self::OPTIONS_CACHE_TTL_SECONDS,
            function () use ($categoryId) {
                return ClassificationDimension::query()
                    ->forCategory($categoryId)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with(['values' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
                    ->get()
                    ->map(fn (ClassificationDimension $dimension) => [
                        'id' => $dimension->id,
                        'label' => $dimension->label,
                        'slug' => $dimension->slug,
                        'values' => $dimension->values->map(fn (ClassificationValue $value) => [
                            'id' => $value->id,
                            'value' => $value->value,
                        ])->values(),
                    ])
                    ->values();
            }
        );

        return ['attributes' => $list, 'dimensions' => $list];
    }

    public function indexPayload(Request $request): array
    {
        $subcategoriesAll = Category::hierarchyRowsForAdminDisplay()
            ->filter(fn (Category $category) => $category->parent_category_id !== null)
            ->sortBy(fn (Category $category) => mb_strtolower((string) ($category->name ?? '')))
            ->values();

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $subcategories = new LengthAwarePaginator(
            $subcategoriesAll->forPage($currentPage, $perPage)->values(),
            $subcategoriesAll->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url()],
        );
        $subcategories->withQueryString();
        $this->attachDimensionCounts($subcategories);

        return [
            'subcategories' => $subcategories->getCollection()->map(fn (Category $category): array => [
                'category_id' => (int) $category->category_id,
                'name' => $category->name,
                'parent_name' => optional($category->parent)->name,
                'dimensions_count' => (int) ($category->getAttribute('classification_dimensions_count') ?? 0),
            ])->values()->all(),
            'pagination' => ListPaginationPayload::from($subcategories),
        ];
    }

    public function showPayload(Category $category): array
    {
        $this->assertSubcategory($category);
        $category->load('parent:category_id,name');

        $attributes = ClassificationDimension::withTrashed()
            ->forCategory((int) $category->category_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->withCount(['values' => fn ($query) => $query->whereNull('deleted_at')])
            ->get();

        return [
            'category' => [
                'category_id' => (int) $category->category_id,
                'name' => $category->name,
                'parent_name' => optional($category->parent)->name,
            ],
            'attributes' => $attributes->map(fn (ClassificationDimension $dimension): array => [
                'id' => (int) $dimension->id,
                'label' => $dimension->label,
                'slug' => $dimension->slug,
                'values_count' => (int) $dimension->values_count,
                'trashed' => $dimension->trashed(),
            ])->values()->all(),
        ];
    }

    public function createDimension(Category $category, array $data): ClassificationDimension
    {
        $this->assertSubcategory($category);
        $data['slug'] = $data['slug'] ?? $this->generateUniqueSlug($data['label'], (int) $category->category_id);
        $data['category_id'] = $category->category_id;
        $data['sort_order'] = ((int) (ClassificationDimension::withTrashed()
            ->where('category_id', $category->category_id)
            ->max('sort_order') ?? -1)) + 1;

        $dimension = ClassificationDimension::create($data);
        $this->forgetOptions((int) $category->category_id);

        return $dimension;
    }

    public function updateDimension(ClassificationDimension $dimension, array $data): Category
    {
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $data['slug'] = $this->generateUniqueSlug($data['label'], (int) $dimension->category_id, $dimension->id);
        $dimension->update($data);
        $this->forgetOptions((int) $dimension->category->category_id);

        return $dimension->category;
    }

    public function deleteDimension(ClassificationDimension $dimension): Category
    {
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $category = $dimension->category;
        $dimension->delete();
        $this->forgetOptions((int) $category->category_id);

        return $category;
    }

    public function restoreDimension(int $dimensionId): Category
    {
        $dimension = ClassificationDimension::withTrashed()->findOrFail($dimensionId);
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $dimension->restore();
        $this->forgetOptions((int) $dimension->category->category_id);

        return $dimension->category;
    }

    public function valuesPayload(ClassificationDimension $dimension): array
    {
        $dimension->load(['category.parent', 'values' => fn ($query) => $query->withTrashed()->orderBy('sort_order')->orderBy('id')]);
        $this->assertSubcategory($dimension->category);

        return [
            'dimension' => [
                'id' => (int) $dimension->id,
                'label' => $dimension->label,
                'category_id' => (int) $dimension->category_id,
                'category_name' => optional($dimension->category)->name,
                'parent_name' => optional(optional($dimension->category)->parent)->name,
            ],
            'values' => $dimension->values->map(fn (ClassificationValue $value): array => [
                'id' => (int) $value->id,
                'value' => $value->value,
                'trashed' => $value->trashed(),
            ])->values()->all(),
        ];
    }

    public function createValue(ClassificationDimension $dimension, array $data): ClassificationValue
    {
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $data['classification_dimension_id'] = $dimension->id;
        $data['normalized_value'] = ClassificationValue::normalizeStoredValue($data['value']);
        $data['sort_order'] = ((int) (ClassificationValue::withTrashed()
            ->where('classification_dimension_id', $dimension->id)
            ->max('sort_order') ?? -1)) + 1;

        $value = ClassificationValue::create($data);
        $this->forgetOptions((int) $dimension->category->category_id);

        return $value;
    }

    public function updateValue(ClassificationValue $value, array $data): ClassificationDimension
    {
        $value->load('dimension.category');
        $dimension = $value->dimension;
        $this->assertSubcategory($dimension->category);
        $data['normalized_value'] = ClassificationValue::normalizeStoredValue($data['value']);
        $value->update($data);
        $this->forgetOptions((int) $dimension->category->category_id);

        return $dimension;
    }

    public function deleteValue(ClassificationValue $value): ClassificationDimension
    {
        $value->load('dimension.category');
        $dimension = $value->dimension;
        $this->assertSubcategory($dimension->category);
        $value->delete();
        $this->forgetOptions((int) $dimension->category->category_id);

        return $dimension;
    }

    public function restoreValue(int $valueId): ClassificationDimension
    {
        $value = ClassificationValue::withTrashed()->findOrFail($valueId);
        $value->load('dimension.category');
        $dimension = $value->dimension;
        $this->assertSubcategory($dimension->category);
        $value->restore();
        $this->forgetOptions((int) $dimension->category->category_id);

        return $dimension;
    }

    public function assertSubcategory(Category $category): void
    {
        if ($category->parent_category_id === null) {
            abort(404);
        }
    }

    public function forgetOptions(int $categoryId): void
    {
        Cache::forget($this->optionsCacheKey($categoryId));
    }

    private function attachDimensionCounts(LengthAwarePaginator $subcategories): void
    {
        $ids = $subcategories->getCollection()->pluck('category_id')->all();
        if ($ids === []) {
            return;
        }

        $counts = DB::table('classification_dimensions')
            ->whereIn('category_id', $ids)
            ->whereNull('deleted_at')
            ->selectRaw('category_id, count(*) as c')
            ->groupBy('category_id')
            ->pluck('c', 'category_id');

        foreach ($subcategories as $category) {
            $category->setAttribute('classification_dimensions_count', (int) ($counts[$category->category_id] ?? 0));
        }
    }

    private function generateUniqueSlug(string $label, int $categoryId, ?int $ignoreId = null): string
    {
        $base = Str::slug($label) ?: 'attr';
        $slug = $base;
        $i = 2;

        while (true) {
            $query = ClassificationDimension::withTrashed()
                ->where('category_id', $categoryId)
                ->where('slug', $slug);

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            if (! $query->exists()) {
                return $slug;
            }

            $slug = $base.'-'.$i;
            $i++;
        }
    }

    private function optionsCacheKey(int $categoryId): string
    {
        return 'classification.catalog.options.'.$categoryId;
    }
}
