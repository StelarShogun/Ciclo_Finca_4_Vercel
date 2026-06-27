<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\UpdateProductClassificationsRequest;
use App\Models\ClassificationDimension;
use App\Models\Product;
use App\Services\Admin\Classifications\ProductClassificationAssignmentService;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * CF4-84 — Asignar valores de atributos por producto (por subcategoría).
 */
class ProductClassificationController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $products = Product::query()
            ->whereHas('category', fn ($q) => $q->whereNotNull('parent_category_id'))
            ->with([
                'category.parent',
                'classificationValues' => fn ($q) => $q->with('dimension'),
            ])
            ->orderByDesc('product_id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/ProductClassifications/Index', [
            'products' => $products->getCollection()->map(fn (Product $product): array => [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'parent_category' => optional(optional($product->category)->parent)->name,
                'subcategory' => optional($product->category)->name,
                'values' => $product->classificationValues->map(fn ($cv): array => [
                    'id' => (int) $cv->getKey(),
                    'dimension' => optional($cv->dimension)->label,
                    'value' => $cv->value,
                ])->values()->all(),
            ])->values()->all(),
            'pagination' => ListPaginationPayload::from($products),
        ]);
    }

    public function edit(Product $product): RedirectResponse|InertiaResponse
    {
        $product->load(['category.parent', 'classificationValues.dimension']);

        if (! $product->category || $product->category->parent_category_id === null) {
            return redirect()
                ->route('admin.product-classifications.index')
                ->with('error', 'Primero ubicá el producto en un tipo concreto (ej. «MTB»), no solo en la categoría padre. Desde Inventario podés cambiarlo.');
        }

        $attributes = ClassificationDimension::query()
            ->forCategory((int) $product->category_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['values' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->get();

        $selectedByAttribute = [];
        foreach ($product->classificationValues as $cv) {
            $pivot = $cv->getRelationValue('pivot');
            if ($pivot instanceof Pivot) {
                $selectedByAttribute[(int) $pivot->getAttribute('classification_dimension_id')] = (int) $cv->getKey();
            }
        }

        return Inertia::render('Admin/ProductClassifications/Edit', [
            'product' => [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'parent_category' => optional(optional($product->category)->parent)->name,
                'subcategory' => optional($product->category)->name,
            ],
            'attributes' => $attributes->map(fn (ClassificationDimension $attribute): array => [
                'id' => (int) $attribute->id,
                'label' => $attribute->label,
                'selected' => $selectedByAttribute[$attribute->id] ?? null,
                'values' => $attribute->values->map(fn ($val): array => [
                    'id' => (int) $val->id,
                    'value' => $val->value,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    public function update(UpdateProductClassificationsRequest $request, Product $product, ProductClassificationAssignmentService $service): RedirectResponse
    {
        $product->loadMissing('category');
        if (! $product->category || $product->category->parent_category_id === null) {
            return redirect()
                ->route('admin.product-classifications.index')
                ->with('error', 'El producto debe estar en un tipo concreto del catálogo.');
        }

        $ids = $request->validated('classification_value_ids') ?? [];
        $service->syncForProduct($product, is_array($ids) ? $ids : []);

        return redirect()
            ->route('admin.product-classifications.index')
            ->with('status', 'Datos guardados.');
    }
}
