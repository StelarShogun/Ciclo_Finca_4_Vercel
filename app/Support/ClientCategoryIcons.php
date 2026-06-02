<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;

/**
 * @deprecated Use {@see \App\Services\Client\Storefront\ClientCategoryIcons}.
 */
final class ClientCategoryIcons
{
    public const DEFAULT_ICON = \App\Services\Client\Storefront\ClientCategoryIcons::DEFAULT_ICON;

    public static function iconClassForProduct(Product $product): string
    {
        return \App\Services\Client\Storefront\ClientCategoryIcons::iconClassForProduct($product);
    }

    /**
     * @param  array{parentCategory?: ?Category, subcategory?: ?Category}  $taxonomy
     */
    public static function iconClassForTaxonomy(array $taxonomy): string
    {
        return \App\Services\Client\Storefront\ClientCategoryIcons::iconClassForTaxonomy($taxonomy);
    }

    /**
     * @param  iterable<int, string|null>  $names
     */
    public static function iconClassForNames(iterable $names): string
    {
        return \App\Services\Client\Storefront\ClientCategoryIcons::iconClassForNames($names);
    }

    public static function iconClassForName(?string $name): string
    {
        return \App\Services\Client\Storefront\ClientCategoryIcons::iconClassForName($name);
    }
}
