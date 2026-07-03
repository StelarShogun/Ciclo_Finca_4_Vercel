<?php

namespace App\Http\Controllers\Api\V1\Admin;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Catálogo de clasificaciones ("Opciones por tipo") para el SPA Next: CRUD de
 * dimensiones (atributos) y sus valores por subcategoría, con soft-delete y
 * restauración. Reusa ClassificationCatalogService. Las respuestas devuelven el
 * payload recargado (showPayload/valuesPayload) para refrescar la vista.
 */
final class ClassificationCatalogController extends Controller
{
    public function index(Request $request, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('viewAny', ClassificationDimension::class);

        return response()->json(['data' => $catalog->indexPayload($request)]);
    }

    public function show(Category $category, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('viewAny', ClassificationDimension::class);

        return response()->json(['data' => $catalog->showPayload($category)]);
    }

    public function values(ClassificationDimension $dimension, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('viewAny', ClassificationDimension::class);

        return response()->json(['data' => $catalog->valuesPayload($dimension)]);
    }

    // --- Dimensiones (atributos) ---

    public function storeDimension(StoreClassificationDimensionRequest $request, Category $category, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('create', ClassificationDimension::class);

        $catalog->createDimension($category, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Atributo creado.',
            'data' => $catalog->showPayload($category->fresh() ?? $category),
        ], 201);
    }

    public function updateDimension(UpdateClassificationDimensionRequest $request, ClassificationDimension $dimension, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('update', $dimension);

        $category = $catalog->updateDimension($dimension, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Atributo actualizado.',
            'data' => $catalog->showPayload($category),
        ]);
    }

    public function destroyDimension(ClassificationDimension $dimension, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('delete', $dimension);

        $category = $catalog->deleteDimension($dimension);

        return response()->json([
            'success' => true,
            'message' => 'Atributo eliminado.',
            'data' => $catalog->showPayload($category),
        ]);
    }

    public function restoreDimension(int $dimensionId, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('create', ClassificationDimension::class);

        $category = $catalog->restoreDimension($dimensionId);

        return response()->json([
            'success' => true,
            'message' => 'Atributo restaurado.',
            'data' => $catalog->showPayload($category),
        ]);
    }

    // --- Valores ---

    public function storeValue(StoreClassificationValueRequest $request, ClassificationDimension $dimension, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('create', ClassificationDimension::class);

        $catalog->createValue($dimension, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Valor creado.',
            'data' => $catalog->valuesPayload($dimension->fresh() ?? $dimension),
        ], 201);
    }

    public function updateValue(UpdateClassificationValueRequest $request, ClassificationValue $value, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('update', ClassificationDimension::class);

        $dimension = $catalog->updateValue($value, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Valor actualizado.',
            'data' => $catalog->valuesPayload($dimension),
        ]);
    }

    public function destroyValue(ClassificationValue $value, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('delete', ClassificationDimension::class);

        $dimension = $catalog->deleteValue($value);

        return response()->json([
            'success' => true,
            'message' => 'Valor eliminado.',
            'data' => $catalog->valuesPayload($dimension),
        ]);
    }

    public function restoreValue(int $valueId, ClassificationCatalogService $catalog): JsonResponse
    {
        $this->authorizeAdmin('create', ClassificationDimension::class);

        $dimension = $catalog->restoreValue($valueId);

        return response()->json([
            'success' => true,
            'message' => 'Valor restaurado.',
            'data' => $catalog->valuesPayload($dimension),
        ]);
    }

    private function authorizeAdmin(string $ability, mixed $arguments): void
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize($ability, $arguments);
    }
}
