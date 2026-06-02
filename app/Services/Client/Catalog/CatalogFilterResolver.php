<?php

namespace App\Services\Client\Catalog;

use App\Data\Client\Catalog\CatalogFilterResolution;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;

final class CatalogFilterResolver
{
    public function resolve(Request $request): CatalogFilterResolution
    {
        $selectedBrand = null;
        if ($request->filled('brand_id')) {
            $brandId = (int) $request->brand_id;
            $selectedBrand = Brand::find($brandId);
        }

        $selectedCategory = null;
        $subcategories = collect();
        $parentCategoryForSubcats = null;

        if ($request->filled('category_id')) {
            $selectedCategory = Category::find((int) $request->category_id);
            if ($selectedCategory) {
                if (is_null($selectedCategory->parent_category_id)) {
                    $subcategories = Category::where('parent_category_id', $selectedCategory->category_id)
                        ->orderBy('name')
                        ->get();
                    $parentCategoryForSubcats = $selectedCategory;
                } else {
                    $parentCategoryForSubcats = $selectedCategory->parent()->first();
                    if ($parentCategoryForSubcats instanceof Category) {
                        $subcategories = Category::where('parent_category_id', $parentCategoryForSubcats->category_id)
                            ->orderBy('name')
                            ->get();
                    }
                }
            }
        }

        $minPrice = $request->filled('min_price') ? $request->input('min_price') : null;
        $maxPrice = $request->filled('max_price') ? $request->input('max_price') : null;

        $minNegative = is_numeric($minPrice) && (float) $minPrice < 0;
        $maxNegative = is_numeric($maxPrice) && (float) $maxPrice < 0;
        if ($minNegative || $maxNegative) {
            return new CatalogFilterResolution(
                selectedBrand: $selectedBrand,
                selectedCategory: $selectedCategory,
                subcategories: $subcategories,
                parentCategoryForSubcats: $parentCategoryForSubcats,
                priceValidationRedirect: redirect()->route('clients.catalog', $request->except(['min_price', 'max_price', 'page']))
                    ->withInput()
                    ->withErrors(['price_range' => 'Los precios del filtro no pueden ser negativos.']),
            );
        }

        if (is_numeric($minPrice) && is_numeric($maxPrice) && (float) $minPrice > (float) $maxPrice) {
            return new CatalogFilterResolution(
                selectedBrand: $selectedBrand,
                selectedCategory: $selectedCategory,
                subcategories: $subcategories,
                parentCategoryForSubcats: $parentCategoryForSubcats,
                priceValidationRedirect: redirect()->route('clients.catalog', $request->except(['min_price', 'max_price', 'page']))
                    ->withInput()
                    ->withErrors(['price_range' => 'El precio mínimo debe ser menor o igual al precio máximo.']),
            );
        }

        return new CatalogFilterResolution(
            selectedBrand: $selectedBrand,
            selectedCategory: $selectedCategory,
            subcategories: $subcategories,
            parentCategoryForSubcats: $parentCategoryForSubcats,
            priceValidationRedirect: null,
        );
    }

    public function activeFilterCount(Request $request): int
    {
        return collect(['min_price', 'max_price', 'brand_id', 'search'])
            ->filter(fn (string $key): bool => $request->filled($key))
            ->count();
    }
}
