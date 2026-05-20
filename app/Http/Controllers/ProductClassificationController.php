<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductClassificationsRequest;
use App\Models\ClassificationDimension;
use App\Models\Product;
use App\Services\ProductClassificationAssignmentService;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CF4-84 — Asignar valores de atributos por producto (por subcategoría).
 */
class ProductClassificationController extends Controller
{
    public function index(Request $request): View
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

        return view('admin.product-classifications.index', compact('products'));
    }

    public function edit(Product $product): RedirectResponse|View
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

        return view('admin.product-classifications.edit', compact('product', 'attributes', 'selectedByAttribute'));
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
