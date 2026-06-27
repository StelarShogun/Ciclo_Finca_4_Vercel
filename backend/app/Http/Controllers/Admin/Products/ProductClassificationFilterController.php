<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Services\Admin\Products\InventoryClassificationFilterService;
use Illuminate\Http\Request;

final class ProductClassificationFilterController extends Controller
{
    public function __construct(
        private InventoryClassificationFilterService $classificationFilters,
    ) {}

    public function options(Request $request)
    {
        return response()->json([
            'success' => true,
            'filters' => $this->classificationFilters->filtersForRequest($request),
        ]);
    }

    public function dimensions(Request $request)
    {
        return response()->json([
            'success' => true,
            'dimensions' => collect($this->classificationFilters->filtersForRequest($request))
                ->map(fn (array $filter) => [
                    'slug' => $filter['slug'],
                    'label' => $filter['label'],
                ])
                ->values(),
        ]);
    }

    public function suggest(Request $request, string $slug)
    {
        return response()->json([
            'success' => true,
            'options' => $this->classificationFilters->suggest($request, $slug),
        ]);
    }
}
