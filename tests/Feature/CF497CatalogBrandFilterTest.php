<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CF497CatalogBrandFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Brand, 1: Brand, 2: Product, 3: Product, 4: Category}
     */
    private function seedTwoBrandsWithActiveProducts(): array
    {
        $category = Category::create([
            'name' => 'CF497 Categoría',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $brandA = Brand::create(['name' => 'Marca A CF497']);
        $brandB = Brand::create(['name' => 'Marca B CF497']);

        $productA = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'Producto Marca A',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 500,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
        $productA->brands()->attach($brandA->id);

        $productB = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'Producto Marca B',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 2000,
            'purchase_price' => 800,
            'stock_current' => 3,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
        $productB->brands()->attach($brandB->id);

        return [$brandA, $brandB, $productA, $productB, $category];
    }

    public function test_brand_filter_returns_only_products_of_selected_brand(): void
    {
        [$brandA, $brandB, $productA, $productB] = $this->seedTwoBrandsWithActiveProducts();

        $this->get(route('clients.catalog', ['brand_id' => $brandA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('products', function ($products) use ($productA, $productB) {
                    $ids = collect($products)->pluck('id')->all();

                    return in_array((int) $productA->product_id, $ids, true)
                        && ! in_array((int) $productB->product_id, $ids, true);
                })
            );
    }

    public function test_brand_filter_combined_with_category_and_price_filters_applies_without_errors(): void
    {
        [$brandA, , , , $category] = $this->seedTwoBrandsWithActiveProducts();

        $this->get(route('clients.catalog', [
            'brand_id' => $brandA->id,
            'category_id' => $category->category_id,
            'min_price' => '0',
            'max_price' => '9999999',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->has('products')
                ->has('pagination')
            );
    }

    public function test_brand_and_category_filter_returns_only_products_matching_both(): void
    {
        [$brandA, , $productA] = $this->seedTwoBrandsWithActiveProducts();

        $this->get(route('clients.catalog', [
            'brand_id' => $brandA->id,
            'category_id' => $productA->category_id,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('products', function ($products) use ($brandA, $productA) {
                    foreach ($products as $row) {
                        $belongsToBrand = collect($row['brands'] ?? [])->contains('id', $brandA->id);
                        $inCategory = (int) ($row['category']['id'] ?? 0) === (int) $productA->category_id;
                        if (! $belongsToBrand || ! $inCategory) {
                            return false;
                        }
                    }

                    return true;
                })
            );
    }

    public function test_nonexistent_brand_returns_empty_list_without_error(): void
    {
        $this->get(route('clients.catalog', ['brand_id' => PHP_INT_MAX]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('pagination.total', 0)
                ->where('products', [])
            );
    }

    public function test_brand_with_no_active_products_returns_empty_list(): void
    {
        $brand = Brand::create(['name' => 'Marca Sin Activos CF497']);
        $category = Category::create([
            'name' => 'CF497 Inactiva',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $inactive = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'Producto Inactivo CF497',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 500,
            'purchase_price' => 100,
            'stock_current' => 0,
            'stock_minimum' => 1,
            'status' => 'inactive',
        ]);
        $inactive->brands()->attach($brand->id);

        $this->get(route('clients.catalog', ['brand_id' => $brand->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('pagination.total', 0)
            );
    }

    public function test_brand_filter_is_reflected_in_url_and_preserved_in_paginator(): void
    {
        [$brandA] = $this->seedTwoBrandsWithActiveProducts();

        $this->get(route('clients.catalog', ['brand_id' => $brandA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('pagination.links', function ($links) use ($brandA) {
                    $url = collect($links)->firstWhere('active', true)['url'] ?? '';

                    return str_contains((string) $url, 'brand_id='.$brandA->id);
                })
            );
    }

    public function test_brand_filter_does_not_break_price_filter_behavior(): void
    {
        [$brandA] = $this->seedTwoBrandsWithActiveProducts();

        $this->get(route('clients.catalog', [
            'brand_id' => $brandA->id,
            'min_price' => '999999',
            'max_price' => '999999',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('products', function ($products) {
                    foreach ($products as $row) {
                        $price = (float) ($row['price'] ?? 0);
                        if ($price < 999999.0 || $price > 999999.0) {
                            return false;
                        }
                    }

                    return true;
                })
            );
    }
}
