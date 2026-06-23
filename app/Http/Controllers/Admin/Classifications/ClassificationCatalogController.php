<?php

namespace App\Http\Controllers\Admin\Classifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Classifications\StoreClassificationDimensionRequest;
use App\Http\Requests\Admin\Classifications\StoreClassificationValueRequest;
use App\Http\Requests\Admin\Classifications\UpdateClassificationDimensionRequest;
use App\Http\Requests\Admin\Classifications\UpdateClassificationValueRequest;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminPerPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * CF4-84 — Admin: atributos (Color, Talla…) y valores por subcategoría.
 */
class ClassificationCatalogController extends Controller
{
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
                break;
            }

            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * JSON para el inventario: atributos y valores posibles por subcategoría.
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

    public function index(Request $request): InertiaResponse
    {
        $subcategoriesAll = Category::hierarchyRowsForAdminDisplay()
            ->filter(fn (Category $c) => $c->parent_category_id !== null)
            ->sortBy(fn (Category $c) => mb_strtolower((string) ($c->name ?? '')))
            ->values();

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $subcategories = new LengthAwarePaginator(
            $subcategoriesAll->forPage($currentPage, $perPage)->values(),
            $subcategoriesAll->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url()]
        );
        $subcategories->withQueryString();

        $ids = $subcategories->getCollection()->pluck('category_id')->all();
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

        return Inertia::render('Admin/Classifications/Index', [
            'subcategories' => $subcategories->getCollection()->map(fn (Category $c): array => [
                'category_id' => (int) $c->category_id,
                'name' => $c->name,
                'parent_name' => optional($c->parent)->name,
                'dimensions_count' => (int) ($c->getAttribute('classification_dimensions_count') ?? 0),
            ])->values()->all(),
            'pagination' => ListPaginationPayload::from($subcategories),
        ]);
    }

    public function showCategory(Category $category): InertiaResponse
    {
        $this->assertSubcategory($category);
        $category->load('parent:category_id,name');
        $attributes = ClassificationDimension::withTrashed()
            ->forCategory((int) $category->category_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->withCount(['values' => fn ($q) => $q->whereNull('deleted_at')])
            ->get();

        return Inertia::render('Admin/Classifications/Show', [
            'category' => [
                'category_id' => (int) $category->category_id,
                'name' => $category->name,
                'parent_name' => optional($category->parent)->name,
            ],
            'attributes' => $attributes->map(fn (ClassificationDimension $d): array => [
                'id' => (int) $d->id,
                'label' => $d->label,
                'slug' => $d->slug,
                'values_count' => (int) $d->values_count,
                'trashed' => $d->trashed(),
            ])->values()->all(),
        ]);
    }

    public function storeDimension(StoreClassificationDimensionRequest $request, Category $category): JsonResponse|RedirectResponse
    {
        $this->assertSubcategory($category);
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $this->generateUniqueSlug($data['label'], (int) $category->category_id);
        $data['category_id'] = $category->category_id;

        $maxOrder = ClassificationDimension::withTrashed()
            ->where('category_id', $category->category_id)
            ->max('sort_order') ?? -1;
        $data['sort_order'] = $maxOrder + 1;

        /** @var ClassificationDimension $dimension */
        $dimension = ClassificationDimension::create($data);
        self::forgetClassificationOptionsCacheForCategory((int) $category->category_id);

        if ($request->expectsJson()) {
            return response()->json([
                'dimension' => [
                    'id' => $dimension->id,
                    'label' => $dimension->label,
                    'slug' => $dimension->slug,
                    'values' => [],
                ],
            ], 201);
        }

        return redirect()
            ->route('admin.classifications.catalog.show', $category)
            ->with('status', 'Atributo creado.');
    }

    public function editDimension(ClassificationDimension $dimension): RedirectResponse
    {
        // La edición se hace en un modal de la vista Inertia; redirigimos al detalle.
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);

        return redirect()->route('admin.classifications.catalog.show', $dimension->category);
    }

    public function updateDimension(UpdateClassificationDimensionRequest $request, ClassificationDimension $dimension): RedirectResponse
    {
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $data = $request->validated();
        $data['slug'] = $this->generateUniqueSlug($data['label'], (int) $dimension->category_id, $dimension->id);
        $dimension->update($data);
        self::forgetClassificationOptionsCacheForCategory((int) $dimension->category->category_id);

        return redirect()
            ->route('admin.classifications.catalog.show', $dimension->category)
            ->with('status', 'Atributo actualizado.');
    }

    public function destroyDimension(Request $request, ClassificationDimension $dimension): RedirectResponse|JsonResponse
    {
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $catId = (int) $dimension->category->category_id;
        $dimension->delete();
        self::forgetClassificationOptionsCacheForCategory($catId);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Atributo desactivado. Los productos que ya tenían un valor siguen igual.',
            ]);
        }

        return redirect()
            ->route('admin.classifications.catalog.show', $dimension->category)
            ->with('status', 'Atributo desactivado. Los productos que ya tenían un valor siguen igual.');
    }

    public function restoreDimension(int $dimensionId): RedirectResponse
    {
        $dimension = ClassificationDimension::withTrashed()->findOrFail($dimensionId);
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $dimension->restore();
        self::forgetClassificationOptionsCacheForCategory((int) $dimension->category->category_id);

        return redirect()
            ->route('admin.classifications.catalog.show', $dimension->category)
            ->with('status', 'Atributo activado de nuevo.');
    }

    public function indexValues(ClassificationDimension $dimension): InertiaResponse
    {
        $dimension->load(['category.parent', 'values' => fn ($q) => $q->withTrashed()->orderBy('sort_order')->orderBy('id')]);
        $this->assertSubcategory($dimension->category);

        return Inertia::render('Admin/Classifications/Values', [
            'dimension' => [
                'id' => (int) $dimension->id,
                'label' => $dimension->label,
                'category_id' => (int) $dimension->category_id,
                'category_name' => optional($dimension->category)->name,
                'parent_name' => optional(optional($dimension->category)->parent)->name,
            ],
            'values' => $dimension->values->map(fn (ClassificationValue $v): array => [
                'id' => (int) $v->id,
                'value' => $v->value,
                'trashed' => $v->trashed(),
            ])->values()->all(),
        ]);
    }

    public function storeValue(StoreClassificationValueRequest $request, ClassificationDimension $dimension): JsonResponse|RedirectResponse
    {
        $dimension->load('category');
        $this->assertSubcategory($dimension->category);
        $data = $request->validated();
        $data['classification_dimension_id'] = $dimension->id;
        $data['normalized_value'] = ClassificationValue::normalizeStoredValue($data['value']);

        $maxOrder = ClassificationValue::withTrashed()
            ->where('classification_dimension_id', $dimension->id)
            ->max('sort_order') ?? -1;
        $data['sort_order'] = $maxOrder + 1;

        /** @var ClassificationValue $value */
        $value = ClassificationValue::create($data);
        self::forgetClassificationOptionsCacheForCategory((int) $dimension->category->category_id);

        if ($request->expectsJson()) {
            return response()->json([
                'value' => [
                    'id' => $value->id,
                    'value' => $value->value,
                    'classification_dimension_id' => $dimension->id,
                ],
            ], 201);
        }

        return redirect()
            ->route('admin.classifications.values.index', $dimension)
            ->with('status', 'Valor añadido.');
    }

    public function editValue(ClassificationValue $value): RedirectResponse
    {
        // La edición se hace en un modal de la vista Inertia; redirigimos al listado de valores.
        $value->load('dimension.category');
        $dimension = $value->dimension;
        $this->assertSubcategory($dimension->category);

        return redirect()->route('admin.classifications.values.index', $dimension);
    }

    public function updateValue(UpdateClassificationValueRequest $request, ClassificationValue $value): RedirectResponse
    {
        $value->load('dimension.category');
        $dimension = $value->dimension;
        $this->assertSubcategory($dimension->category);
        $data = $request->validated();
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
            ->with('status', 'Valor desactivado.');
    }

    public function restoreValue(int $valueId): RedirectResponse
    {
        $value = ClassificationValue::withTrashed()->findOrFail($valueId);
        $value->load('dimension.category');
        $dimension = $value->dimension;
        $this->assertSubcategory($dimension->category);
        $value->restore();
        self::forgetClassificationOptionsCacheForCategory((int) $dimension->category->category_id);

        return redirect()
            ->route('admin.classifications.values.index', $dimension)
            ->with('status', 'Valor activado de nuevo.');
    }
}
