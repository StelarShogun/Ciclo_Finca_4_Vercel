<?php

namespace App\Data\Client\Catalog;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

final readonly class CatalogFilterResolution
{
    /**
     * @param  Collection<int, Category>  $subcategories
     */
    public function __construct(
        public ?Brand $selectedBrand,
        public ?Category $selectedCategory,
        public Collection $subcategories,
        public ?Category $parentCategoryForSubcats,
        public ?RedirectResponse $priceValidationRedirect,
    ) {}
}
