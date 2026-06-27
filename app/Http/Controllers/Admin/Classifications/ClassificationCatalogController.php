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
use App\Services\Admin\Classifications\ClassificationCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * CF4-84 — Admin: atributos (Color, Talla…) y valores por subcategoría.
 */
class ClassificationCatalogController extends Controller
{
    public static function forgetClassificationOptionsCacheForCategory(int $categoryId): void
    {
        app(ClassificationCatalogService::class)->forgetOptions($categoryId);
    }

    /**
     * JSON para el inventario: atributos y valores posibles por subcategoría.
     */
    public function optionsForCategory(Category $category, ClassificationCatalogService $catalog): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', ClassificationDimension::class);

        return response()->json($catalog->optionsPayload($category));
    }

    public function index(Request $request, ClassificationCatalogService $catalog): InertiaResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', ClassificationDimension::class);

        return Inertia::render('Admin/Classifications/Index', $catalog->indexPayload($request));
    }

    public function showCategory(Category $category, ClassificationCatalogService $catalog): InertiaResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', ClassificationDimension::class);

        return Inertia::render('Admin/Classifications/Show', $catalog->showPayload($category));
    }

    public function storeDimension(StoreClassificationDimensionRequest $request, Category $category, ClassificationCatalogService $catalog): JsonResponse|RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', ClassificationDimension::class);

        $dimension = $catalog->createDimension($category, $request->validated());

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

    public function editDimension(ClassificationDimension $dimension, ClassificationCatalogService $catalog): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $dimension);

        // La edición se hace en un modal de la vista Inertia; redirigimos al detalle.
        $dimension->load('category');
        $catalog->assertSubcategory($dimension->category);

        return redirect()->route('admin.classifications.catalog.show', $dimension->category);
    }

    public function updateDimension(UpdateClassificationDimensionRequest $request, ClassificationDimension $dimension, ClassificationCatalogService $catalog): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $dimension);

        $category = $catalog->updateDimension($dimension, $request->validated());

        return redirect()
            ->route('admin.classifications.catalog.show', $category)
            ->with('status', 'Atributo actualizado.');
    }

    public function destroyDimension(Request $request, ClassificationDimension $dimension, ClassificationCatalogService $catalog): RedirectResponse|JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('delete', $dimension);

        $category = $catalog->deleteDimension($dimension);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Atributo desactivado. Los productos que ya tenían un valor siguen igual.',
            ]);
        }

        return redirect()
            ->route('admin.classifications.catalog.show', $category)
            ->with('status', 'Atributo desactivado. Los productos que ya tenían un valor siguen igual.');
    }

    public function restoreDimension(int $dimensionId, ClassificationCatalogService $catalog): RedirectResponse
    {
        $dimension = ClassificationDimension::withTrashed()->findOrFail($dimensionId);
        Gate::forUser(Auth::guard('admin')->user())->authorize('restore', $dimension);

        $category = $catalog->restoreDimension($dimensionId);

        return redirect()
            ->route('admin.classifications.catalog.show', $category)
            ->with('status', 'Atributo activado de nuevo.');
    }

    public function indexValues(ClassificationDimension $dimension, ClassificationCatalogService $catalog): InertiaResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $dimension);

        return Inertia::render('Admin/Classifications/Values', $catalog->valuesPayload($dimension));
    }

    public function storeValue(StoreClassificationValueRequest $request, ClassificationDimension $dimension, ClassificationCatalogService $catalog): JsonResponse|RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', ClassificationValue::class);

        $value = $catalog->createValue($dimension, $request->validated());

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

    public function editValue(ClassificationValue $value, ClassificationCatalogService $catalog): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $value);

        // La edición se hace en un modal de la vista Inertia; redirigimos al listado de valores.
        $value->load('dimension.category');
        $dimension = $value->dimension;
        $catalog->assertSubcategory($dimension->category);

        return redirect()->route('admin.classifications.values.index', $dimension);
    }

    public function updateValue(UpdateClassificationValueRequest $request, ClassificationValue $value, ClassificationCatalogService $catalog): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $value);

        $dimension = $catalog->updateValue($value, $request->validated());

        return redirect()
            ->route('admin.classifications.values.index', $dimension)
            ->with('status', 'Valor actualizado.');
    }

    public function destroyValue(ClassificationValue $value, ClassificationCatalogService $catalog): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('delete', $value);

        $dimension = $catalog->deleteValue($value);

        return redirect()
            ->route('admin.classifications.values.index', $dimension)
            ->with('status', 'Valor desactivado.');
    }

    public function restoreValue(int $valueId, ClassificationCatalogService $catalog): RedirectResponse
    {
        $value = ClassificationValue::withTrashed()->findOrFail($valueId);
        Gate::forUser(Auth::guard('admin')->user())->authorize('restore', $value);

        $dimension = $catalog->restoreValue($valueId);

        return redirect()
            ->route('admin.classifications.values.index', $dimension)
            ->with('status', 'Valor activado de nuevo.');
    }
}
