<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Links catalog products to demo brands (products_brand) after ProductsSeeder.
 */
class ProductBrandSeeder extends Seeder
{
    public function run(): void
    {
        if (! Product::query()->exists()) {
            $this->command->warn('ProductBrandSeeder: no hay productos; se omite.');

            return;
        }

        $brands = Brand::query()->orderBy('name')->get();
        if ($brands->isEmpty()) {
            $this->command->warn('ProductBrandSeeder: no hay marcas; ejecuta BrandSeeder primero.');

            return;
        }

        $sorted = $brands->sortByDesc(fn (Brand $b) => strlen($b->name))->values();

        $linked = 0;

        foreach (Product::query()->get(['product_id', 'name']) as $product) {
            $haystack = $product->name;
            $matched = null;

            foreach ($sorted as $brand) {
                if (stripos($haystack, $brand->name) !== false) {
                    $matched = $brand;
                    break;
                }
            }

            if ($matched === null) {
                continue;
            }

            $product->brands()->syncWithoutDetaching([$matched->id]);
            $linked++;
        }

        $this->command->info("ProductBrandSeeder: {$linked} producto(s) vinculado(s) a marcas.");
    }
}
