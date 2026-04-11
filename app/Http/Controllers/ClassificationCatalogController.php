<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassificationDimensionRequest;
use App\Http\Requests\StoreClassificationValueRequest;
use App\Http\Requests\UpdateClassificationDimensionRequest;
use App\Http\Requests\UpdateClassificationValueRequest;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CF4-84 — Admin: atributos (Color, Talla…) y valores por subcategoría.
 * En código persisten modelos ClassificationDimension / ClassificationValue.
 */
class ClassificationCatalogController extends Controller
{
    /** TTL corto: el modal pide el mismo JSON muchas veces; se invalida al CRUD de atributos/valores. */
    private const OPTIONS_CACHE_TTL_SECONDS = 300;

    private static function classificationOptionsCacheKey(int $categoryId): string
    {
        return 'classification.catalog.options.'.$categoryId;
    }

    public static function forgetClassificationOptionsCacheForCategory(int $categoryId): void
    {
        Cache::forget(self::classificationOptionsCacheKey($categoryId));
    }

    private function assertSubcategory(Category $category): void
    {
        if ($category->parent_category_id === null) {
            abort(404);
        }
    }

    /**
     * JSON para el inventario: atributos y valores posibles por subcategoría.
     * Clave `attributes` (antes `dimensions`); se mantiene alias `dimensions` por compatibilidad.
     */
    public function optionsForCategory(Category $category): JsonResponse
    {
        if ($category->parent_category_id === null) {
            $empty = [];

            return response()->json(['attributes' => $empty, 'dimensions' => $empty]);
        }

        $cid = (int) $category->category_id;
        $list = Cache::remember(
            self::classificationOptionsCacheKey($cid),
            self::OPTIONS_CACHE_TTL_SECONDS,
            function () use ($cid) {
                $dimensions = ClassificationDimension::query()
                    ->forCategory($cid)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with(['values' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
                    ->get();

                return $dimensions->map(fn (ClassificationDimension $d) => [
                    'id' => $d->id,
                    'label' => $d->label,
                    'slug' => $d->slug,
                    'values' => $d->values->map(fn (ClassificationValue $v) => [
                        'id' => $v->id,
                        'value' => $v->value,
                    ])->values(),
                ])->values();
            }
        );

        return response()->json([
            'attributes' => $list,
            'dimensions' => $list,
        ]);
    }

    public function index(): View
    {
        // Misma deduplicación que inventario/jerarquía: varios seeds crean raíces/subs repetidas por nombre.
        $subcategories = Category::hierarchyRowsForAdminDisplay()
            ->filter(fn (Category $c) => $c->parent_category_id !== null)
            ->sortBy(fn (Category $c) => mb_strtolower((string) ($c->name ?? '')))
            ->values();

        $ids = $subcategories->pluck('category_id')->all();
        if ($ids !== []) {
            $counts = DB::table('classification_dimensions')
                ->whereIn('category_id', $ids)
                ->whereNull('deleted_at')
                ->selectRaw('category_id, count(*) as c')
                ->groupBy('category_id')
                ->pluck('c', 'category_id');

            foreach ($subcategories as $cat) {
                $cat->setAttribute(
                    'classification_dimensions_count',
                    (int) ($counts[$cat->category_id] ?? 0)
                );
            }
        }

        return view('admin.classifications.catalog.index', compact('subcategories'));
    }

    public function showCategory(Category $category): View
    {
        $this->assertSubcategory($category);
        $category->load('parent:category_id,name');
        $attributes = ClassificationDimension::query()
            ->forCategory((int) $category->category_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->withCount(['values' => fn ($q) => $q->whereNull('deleted_at')])
            ->get();

        return view('admin.classifications.catalog.show', compact('category', 'attributes'));
    }

    public function storeDimension(StoreClassificationDimensionRequest $request, Category $category): RedirectResponse
    {
        $this->assertSubcategory($category);
        $data = $request->validated();
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['category_id'] = $category->category_id;
        ClassificationDimension::create($data);
        self::forgetClassificationOptionsCacheForCategory((int) $category->category_id);

        return redirect()
            ->route('admin.classifications.catalog.show', $category)
            ->with('status', 'Listo: nuevo atributo para este tipo de producto.');
    }

    public function editDimension(ClassificationDimension $dimension): View
    {
        $dimension->load('category.parent');
        $this->assertSubcategory($dimension->category);

        return view('admin.classifications.catalog.dimension-edit', compact('dimension'));
    }

    public function updateDimension(UpdateClassificationDimensionRequest $request, ClassificationDimension $dimension): RedirectResponse
    {
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $data = $request->validated();
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $dimension->update($data);
        self::forgetClassificationOptionsCacheForCategory((int) $dimension->category->category_id);

        return redirect()
            ->route('admin.classifications.catalog.show', $dimension->category)
            ->with('status', 'Cambios guardados.');
    }

    public function destroyDimension(ClassificationDimension $dimension): RedirectResponse
    {
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $catId = (int) $dimension->category->category_id;
        $dimension->delete();
        self::forgetClassificationOptionsCacheForCategory($catId);

        return redirect()
            ->route('admin.classifications.catalog.show', $dimension->category)
            ->with('status', 'Atributo oculto. Los productos que ya tenían un valor siguen igual hasta que los edites.');
    }

    public function indexValues(ClassificationDimension $dimension): View
    {
        $dimension->load(['category.parent', 'values' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')]);
        $this->assertSubcategory($dimension->category);

        return view('admin.classifications.catalog.values', compact('dimension'));
    }

    public function storeValue(StoreClassificationValueRequest $request, ClassificationDimension $dimension): RedirectResponse
    {
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $data = $request->validated();
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['classification_dimension_id'] = $dimension->id;
        $data['normalized_value'] = ClassificationValue::normalizeStoredValue($data['value']);
        ClassificationValue::create($data);
        self::forgetClassificationOptionsCacheForCategory((int) $dimension->category->category_id);

        return redirect()
            ->route('admin.classifications.values.index', $dimension)
            ->with('status', 'Valor añadido.');
    }

    public function editValue(ClassificationValue $value): View
    {
        $value->load('dimension.category.parent');
        $dimension = $value->dimension;
        $this->assertSubcategory($dimension->category);

        return view('admin.classifications.catalog.value-edit', compact('value', 'dimension'));
    }

    public function updateValue(UpdateClassificationValueRequest $request, ClassificationValue $value): RedirectResponse
    {
        $value->load('dimension.category');
        $dimension = $value->dimension;
        $this->assertSubcategory($dimension->category);
        $data = $request->validated();
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['normalized_value'] = ClassificationValue::normalizeStoredValue($data['value']);
        $value->update($data);
        self::forgetClassificationOptionsCacheForCategory((int) $dimension->category->category_id);

        return redirect()
            ->route('admin.classifications.values.index', $dimension)
            ->with('status', 'Valor actualizado.');
    }

    public function destroyValue(ClassificationValue $value): RedirectResponse
    {
        $value->load('dimension.category');
        $dimension = $value->dimension;
        $this->assertSubcategory($dimension->category);
        $catId = (int) $dimension->category->category_id;
        $value->delete();
        self::forgetClassificationOptionsCacheForCategory($catId);

        return redirect()
            ->route('admin.classifications.values.index', $dimension)
            ->with('status', 'Valor oculto.');
    }
}
