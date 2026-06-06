<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CF497CatalogBrandFilterTest extends TestCase
{
    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('CatalogBrandFilterTest requiere MySQL.');
        }
        foreach (['products', 'brands', 'products_brand', 'categories'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Falta la tabla requerida ({$table}).");
            }
        }
    }

    /** Filters by brand: includes selected brand's products and excludes others. */
    public function test_brand_filter_returns_only_products_of_selected_brand(): void
    {
        $brandA = Brand::where('name', 'like', '%')->orderBy('id')->first();
        $brandB = Brand::where('id', '!=', $brandA?->id)->orderBy('id')->first();

        if (! $brandA || ! $brandB) {
            $this->markTestSkipped('Se necesitan al menos dos marcas en la base de datos.');
        }

        $productOfA = $brandA->products()->whereRaw(
            'LOWER(TRIM(COALESCE(status, \'\'))) IN (\'active\', \'activo\')'
        )->where('stock_current', '>', 0)->first();

        $productOfB = $brandB->products()->whereRaw(
            'LOWER(TRIM(COALESCE(status, \'\'))) IN (\'active\', \'activo\')'
        )->where('stock_current', '>', 0)->first();

        if (! $productOfA instanceof Product || ! $productOfB instanceof Product) {
            $this->markTestSkipped('Se necesita al menos un producto activo por cada una de las dos marcas.');
        }

        $this->get(route('clients.catalog', ['brand_id' => $brandA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('products', function ($products) use ($productOfA, $productOfB) {
                    $ids = collect($products)->pluck('id')->all();

                    return in_array((int) $productOfA->product_id, $ids, true)
                        && ! in_array((int) $productOfB->product_id, $ids, true);
                })
            );
    }

    /** Combining brand, category and price filters produces no errors. */
    public function test_brand_filter_combined_with_category_and_price_filters_applies_without_errors(): void
    {
        $brand = Brand::has('products')->orderBy('id')->first();

        if (! $brand) {
            $this->markTestSkipped('Se necesita al menos una marca con productos en la base de datos.');
        }

        $category = Category::whereNull('parent_category_id')->first();

        $this->get(route('clients.catalog', [
            'brand_id' => $brand->id,
            'category_id' => $category?->category_id,
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

    /** Filtering by brand and category simultaneously returns only products matching both. */
    public function test_brand_and_category_filter_returns_only_products_matching_both(): void
    {
        $brand = Brand::has('products')->orderBy('id')->first();

        if (! $brand) {
            $this->markTestSkipped('Se necesita al menos una marca con productos.');
        }

        $product = $brand->products()->whereRaw(
            'LOWER(TRIM(COALESCE(status, \'\'))) IN (\'active\', \'activo\')'
        )->where('stock_current', '>', 0)->first();

        if (! $product instanceof Product || ! $product->category_id) {
            $this->markTestSkipped('No hay un producto activo con categoría para la marca seleccionada.');
        }

        $this->get(route('clients.catalog', [
            'brand_id' => $brand->id,
            'category_id' => $product->category_id,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('products', function ($products) use ($brand, $product) {
                    foreach ($products as $row) {
                        $belongsToBrand = collect($row['brands'] ?? [])->contains('id', $brand->id);
                        $inCategory = (int) ($row['category']['id'] ?? 0) === (int) $product->category_id;
                        if (! $belongsToBrand || ! $inCategory) {
                            return false;
                        }
                    }

                    return true;
                })
            );
    }

    /** A non-existent brand returns an empty list without error. */
    public function test_nonexistent_brand_returns_empty_list_without_error(): void
    {
        $nonExistentId = PHP_INT_MAX;

        $this->get(route('clients.catalog', ['brand_id' => $nonExistentId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('pagination.total', 0)
                ->where('products', [])
            );
    }

    /** A brand with no active products returns an empty list without error. */
    public function test_brand_with_no_active_products_returns_empty_list(): void
    {
        $brand = Brand::whereDoesntHave('products', function ($q) {
            $q->whereRaw('LOWER(TRIM(COALESCE(status, \'\'))) IN (\'active\', \'activo\')')
                ->where('stock_current', '>', 0);
        })->first();

        if (! $brand) {
            $this->markTestSkipped('No hay ninguna marca sin productos activos en la base de datos.');
        }

        $this->get(route('clients.catalog', ['brand_id' => $brand->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('pagination.total', 0)
            );
    }

    /** The paginator preserves brand_id in the URL when filtering. */
    public function test_brand_filter_is_reflected_in_url_and_preserved_in_paginator(): void
    {
        $brand = Brand::has('products')->orderBy('id')->first();

        if (! $brand) {
            $this->markTestSkipped('Se necesita al menos una marca con productos en la base de datos.');
        }

        $this->get(route('clients.catalog', ['brand_id' => $brand->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('pagination.links', function ($links) use ($brand) {
                    $url = collect($links)->firstWhere('active', true)['url'] ?? '';

                    return str_contains((string) $url, 'brand_id='.$brand->id);
                })
            );
    }

    /** Filtering by brand still respects the price range without breaking its behavior. */
    public function test_brand_filter_does_not_break_price_filter_behavior(): void
    {
        $brand = Brand::has('products')->orderBy('id')->first();

        if (! $brand) {
            $this->markTestSkipped('Se necesita al menos una marca con productos.');
        }

        $this->get(route('clients.catalog', [
            'brand_id' => $brand->id,
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
