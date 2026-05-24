<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;

final class ClientCategoryIcons
{
    public const DEFAULT_ICON = 'fas fa-box';

    public static function iconClassForProduct(Product $product): string
    {
        $category = $product->category;
        if ($category === null) {
            return self::DEFAULT_ICON;
        }

        if ($category->parent_category_id !== null && ! $category->relationLoaded('parent')) {
            $category->load('parent');
        }

        if ($category->parent_category_id !== null) {
            return self::iconClassForNames([
                $category->name,
                $category->parent?->name,
            ]);
        }

        return self::iconClassForName($category->name);
    }

    /**
     * @param  array{parentCategory?: ?Category, subcategory?: ?Category}  $taxonomy
     */
    public static function iconClassForTaxonomy(array $taxonomy): string
    {
        $names = [];
        if (($taxonomy['subcategory'] ?? null) instanceof Category) {
            $names[] = $taxonomy['subcategory']->name;
        }
        if (($taxonomy['parentCategory'] ?? null) instanceof Category) {
            $names[] = $taxonomy['parentCategory']->name;
        }

        return self::iconClassForNames($names);
    }

    /**
     * Pick the first icon matched from the most specific name to the broadest.
     *
     * @param  iterable<int, string|null>  $names
     */
    public static function iconClassForNames(iterable $names): string
    {
        foreach ($names as $name) {
            $icon = self::iconClassForName($name);
            if ($icon !== self::DEFAULT_ICON) {
                return $icon;
            }
        }

        return self::DEFAULT_ICON;
    }

    /** Font Awesome (fas fa-*) by category name heuristic — no icon column in DB. */
    public static function iconClassForName(?string $name): string
    {
        $n = mb_strtolower(trim((string) $name), 'UTF-8');
        if ($n === '') {
            return self::DEFAULT_ICON;
        }

        /** @var array<string, string> Longer / more specific needles first. */
        $pairs = [
            'multiherramienta' => 'fas fa-wrench',
            'transmisión' => 'fas fa-cogs',
            'transmision' => 'fas fa-cogs',
            'portabultos' => 'fas fa-box-open',
            'hidratación' => 'fas fa-tint',
            'hidratacion' => 'fas fa-tint',
            'iluminación' => 'fas fa-lightbulb',
            'iluminacion' => 'fas fa-lightbulb',
            'neumático' => 'fas fa-circle',
            'neumatico' => 'fas fa-circle',
            'bicicleta' => 'fas fa-bicycle',
            'componente' => 'fas fa-cogs',
            'accesorio' => 'fas fa-box-open',
            'herramienta' => 'fas fa-wrench',
            'nutrición' => 'fas fa-apple-alt',
            'nutricion' => 'fas fa-apple-alt',
            'seguridad' => 'fas fa-shield-alt',
            'extractor' => 'fas fa-wrench',
            'culote' => 'fas fa-tshirt',
            'chaqueta' => 'fas fa-tshirt',
            'jersey' => 'fas fa-tshirt',
            'gravel' => 'fas fa-bicycle',
            'híbrida' => 'fas fa-bicycle',
            'hibrida' => 'fas fa-bicycle',
            'urbana' => 'fas fa-bicycle',
            'trail' => 'fas fa-bicycle',
            'enduro' => 'fas fa-bicycle',
            'candado' => 'fas fa-lock',
            'bebida' => 'fas fa-wine-bottle',
            'repuesto' => 'fas fa-cog',
            'freno' => 'fas fa-cog',
            'rueda' => 'fas fa-circle',
            'llanta' => 'fas fa-circle',
            'llave' => 'fas fa-wrench',
            'casco' => 'fas fa-hard-hat',
            'barra' => 'fas fa-apple-alt',
            'gel' => 'fas fa-apple-alt',
            'short' => 'fas fa-tshirt',
            'ropa' => 'fas fa-tshirt',
            'bici' => 'fas fa-bicycle',
            'mtb' => 'fas fa-bicycle',
            'ruta' => 'fas fa-bicycle',
            'luz' => 'fas fa-lightbulb',
            'electr' => 'fas fa-bolt',
        ];

        foreach ($pairs as $needle => $icon) {
            if (str_contains($n, $needle)) {
                return $icon;
            }
        }

        return self::DEFAULT_ICON;
    }
}
