<?php

namespace App\Services\Admin\Products;

use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class AdminInventoryProductQuery
{
    public function filteredQuery(Request $request): Builder
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('subcategory_id')) {
            $query->where('category_id', $request->subcategory_id);
        } elseif ($request->filled('parent_category_id')) {
            $canonicalParentId = (int) $request->parent_category_id;
            $physicalParentIds = Category::physicalRootIdsForCanonicalParent($canonicalParentId);
            $childIds = Category::whereIn('parent_category_id', $physicalParentIds)->pluck('category_id');
            $query->where(function ($q) use ($physicalParentIds, $childIds) {
                $q->whereIn('category_id', $physicalParentIds)
                    ->orWhereIn('category_id', $childIds);
            });
        } elseif ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in-stock':
                    $query->where('stock_minimum', '>', 0)
                        ->whereColumn('stock_current', '>', 'stock_minimum');
                    break;
                case 'low':
                    $query->where('stock_minimum', '>', 0)
                        ->where('stock_current', '>', 0)
                        ->whereColumn('stock_current', '<=', 'stock_minimum');
                    break;
                case 'out':
                    $query->where('stock_current', 0);
                    break;
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $classificationFilters = $request->input('classifications', []);
        if (is_array($classificationFilters)) {
            foreach ($classificationFilters as $slug => $rawValue) {
                $slug = trim((string) $slug);
                if ($slug === '' || ! is_string($rawValue) || trim($rawValue) === '') {
                    continue;
                }
                $normalizedValue = ClassificationValue::normalizeStoredValue($rawValue);
                $query->whereHas('classificationValues', function ($q) use ($slug, $normalizedValue) {
                    $q->where('classification_values.normalized_value', $normalizedValue)
                        ->whereHas('dimension', fn ($d) => $d->where('slug', $slug));
                });
            }
        }

        [$sort, $order] = $this->validatedSort($request->get('sort'), $request->get('order'));
        $query->orderBy($sort, $order);

        return $query;
    }

    /**
     * @return list<string>
     */
    public function exportFilterLines(Request $request): array
    {
        $lines = [];

        if ($request->filled('search')) {
            $lines[] = 'Búsqueda: '.$request->search;
        }
        if ($request->filled('subcategory_id')) {
            $sub = Category::find($request->subcategory_id);
            $lines[] = 'Subcategoría: '.($sub !== null ? $sub->name : '#'.$request->subcategory_id);
        } elseif ($request->filled('parent_category_id')) {
            $canonicalParentId = (int) $request->parent_category_id;
            $roots = Category::physicalRootIdsForCanonicalParent($canonicalParentId);
            $label = Category::whereIn('category_id', $roots)->value('name');
            $lines[] = 'Categoría: '.($label ?? 'ID '.$canonicalParentId);
        } elseif ($request->filled('category_id')) {
            $cat = Category::find($request->category_id);
            $lines[] = 'Categoría (ID): '.($cat !== null ? $cat->name : '#'.$request->category_id);
        }

        if ($request->filled('stock_status')) {
            $lines[] = 'Stock: '.match ($request->stock_status) {
                'in-stock' => 'En stock',
                'low' => 'Stock bajo',
                'out' => 'Sin stock',
                default => (string) $request->stock_status,
            };
        }

        if ($request->filled('status')) {
            $lines[] = 'Estado producto: '.$request->status;
        }
        $classificationFilters = $request->input('classifications', []);
        if (is_array($classificationFilters)) {
            $labelsBySlug = ClassificationDimension::query()
                ->whereIn('slug', array_keys($classificationFilters))
                ->pluck('label', 'slug');

            foreach ($classificationFilters as $slug => $value) {
                if (! is_string($value) || trim($value) === '') {
                    continue;
                }
                $label = $labelsBySlug[$slug] ?? $slug;
                $lines[] = "{$label}: {$value}";
            }
        }

        if (count($lines) === 0) {
            $lines[] = 'Sin filtros adicionales (todos los productos según orden por defecto).';
        }

        return $lines;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function validatedSort(mixed $sort, mixed $order): array
    {
        $allowed = ['product_id', 'name', 'stock_current', 'sale_price', 'status', 'category_id'];
        $col = is_string($sort) && in_array($sort, $allowed, true) ? $sort : 'product_id';
        $dir = is_string($order) && strtolower($order) === 'asc' ? 'asc' : 'desc';

        return [$col, $dir];
    }
}
