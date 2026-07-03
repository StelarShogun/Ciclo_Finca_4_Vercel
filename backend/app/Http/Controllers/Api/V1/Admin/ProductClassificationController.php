<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\UpdateProductClassificationsRequest;
use App\Models\ClassificationDimension;
use App\Models\Product;
use App\Services\Admin\Classifications\ProductClassificationAssignmentService;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Support\AdminDashboardCache;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Clasificaciones del producto para el SPA Next. Las dimensiones y sus valores
 * son por categoría (subcategoría concreta); se elige un valor por dimensión.
 * Replica el form web (edit) y reusa ProductClassificationAssignmentService.
 */
final class ProductClassificationController extends Controller
{
    public function index(int $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $product);

        $product->load(['category.parent', 'classificationValues.dimension']);

        if (! $product->category || $product->category->parent_category_id === null) {
            return response()->json(['data' => [
                'editable' => false,
                'reason' => 'El producto debe estar en un tipo concreto del catálogo (no solo la categoría padre).',
                'attributes' => [],
            ]]);
        }

        $attributes = ClassificationDimension::query()
            ->forCategory((int) $product->category_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['values' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->get();

        $selected = [];
        foreach ($product->classificationValues as $cv) {
            $pivot = $cv->getRelationValue('pivot');
            if ($pivot instanceof Pivot) {
                $selected[(int) $pivot->getAttribute('classification_dimension_id')] = (int) $cv->getKey();
            }
        }

        return response()->json(['data' => [
            'editable' => true,
            'attributes' => $attributes->map(fn (ClassificationDimension $a): array => [
                'id' => (int) $a->id,
                'label' => $a->label,
                'selected' => $selected[$a->id] ?? null,
                'values' => $a->values->map(fn ($v): array => [
                    'id' => (int) $v->id,
                    'value' => $v->value,
                ])->values()->all(),
            ])->values()->all(),
        ]]);
    }

    public function update(UpdateProductClassificationsRequest $request, int $id, ProductClassificationAssignmentService $service): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);

        $product->loadMissing('category');
        if (! $product->category || $product->category->parent_category_id === null) {
            return response()->json(['message' => 'El producto debe estar en un tipo concreto del catálogo.'], 422);
        }

        $ids = $request->validated('classification_value_ids') ?? [];
        $service->syncForProduct($product, is_array($ids) ? $ids : []);

        ClientStorefrontCache::forgetAfterProductMutation();
        AdminDashboardCache::forget();

        return response()->json(['success' => true, 'message' => 'Clasificaciones guardadas.']);
    }
}
