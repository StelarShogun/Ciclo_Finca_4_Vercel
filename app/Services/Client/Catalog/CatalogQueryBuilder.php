<?php

namespace App\Services\Client\Catalog;

use App\Data\Client\Catalog\CatalogFilterResolution;
use App\Models\Category;
use App\Models\Product;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class CatalogQueryBuilder
{
    public function filteredQuery(Request $request, CatalogFilterResolution $filters): Builder
    {
        $query = Product::with([
            'category.parent',
            'brands',
            'media' => static function ($q): void {
                $q->where('collection_name', 'main_image');
            },
        ])->activeInClientStore();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            });
        }

        if ($request->filled('brand_id')) {
            if ($filters->selectedBrand) {
                $query->whereHas('brands', fn ($q) => $q->where('brands.id', (int) $request->brand_id));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($filters->selectedCategory) {
            if (is_null($filters->selectedCategory->parent_category_id)) {
                $childIds = Category::where('parent_category_id', $filters->selectedCategory->category_id)
                    ->pluck('category_id');
                $query->where(function ($q) use ($filters, $childIds) {
                    $q->where('category_id', $filters->selectedCategory->category_id)
                        ->orWhereIn('category_id', $childIds);
                });
            } else {
                $query->where('category_id', $filters->selectedCategory->category_id);
            }
        }

        $minPrice = $request->filled('min_price') ? $request->input('min_price') : null;
        $maxPrice = $request->filled('max_price') ? $request->input('max_price') : null;

        if (is_numeric($minPrice) && is_numeric($maxPrice)) {
            $query->whereBetween('sale_price', [$minPrice, $maxPrice]);
        } elseif (is_numeric($minPrice)) {
            $query->where('sale_price', '>=', $minPrice);
        } elseif (is_numeric($maxPrice)) {
            $query->where('sale_price', '<=', $maxPrice);
        }

        $sort = $request->get('sort', 'created_at');
        $order = $request->get('direction', 'desc');

        match ($sort) {
            'price' => $query->orderBy('sale_price', $order),
            'name' => $query->orderBy('name', $order),
            default => $query->orderBy('created_at', $order),
        };

        return $query;
    }

    public function paginate(Builder $query, Request $request): LengthAwarePaginator
    {
        $perPage = AdminPerPage::resolve($request->input('per_page', 10));

        return $query->paginate($perPage)->withQueryString();
    }
}
