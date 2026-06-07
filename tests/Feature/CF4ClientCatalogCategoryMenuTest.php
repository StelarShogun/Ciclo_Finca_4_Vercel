<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CF4ClientCatalogCategoryMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_includes_category_panel_markup(): void
    {
        $this->get(route('clients.catalog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->has('categories')
                ->has('filters')
                ->has('summary')
            );
    }

    public function test_catalog_redirects_when_price_filter_is_negative(): void
    {
        $response = $this->from(route('clients.catalog'))
            ->get(route('clients.catalog', ['min_price' => '-5']));
        $response->assertRedirect(route('clients.catalog'));
        $response->assertSessionHasErrors('price_range');
    }

    public function test_filter_by_parent_category_includes_child_products(): void
    {
        $parent = Category::create([
            'name' => 'CF4 Padre Menú',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $child = Category::create([
            'name' => 'CF4 Hija Menú',
            'description' => null,
            'parent_category_id' => $parent->category_id,
        ]);
        Product::create([
            'category_id' => $child->category_id,
            'supplier_id' => null,
            'name' => 'Producto En Sub CF4',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $this->get(route('clients.catalog', ['category_id' => $parent->category_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('products.0.name', 'Producto En Sub CF4')
            );
    }

    public function test_filter_by_child_category_shows_only_that_child(): void
    {
        $parent = Category::create([
            'name' => 'CF4 Padre 2',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $childA = Category::create([
            'name' => 'CF4 Hija A',
            'description' => null,
            'parent_category_id' => $parent->category_id,
        ]);
        $childB = Category::create([
            'name' => 'CF4 Hija B',
            'description' => null,
            'parent_category_id' => $parent->category_id,
        ]);
        Product::create([
            'category_id' => $childA->category_id,
            'supplier_id' => null,
            'name' => 'Solo en A',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 500,
            'purchase_price' => 50,
            'stock_current' => 1,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        Product::create([
            'category_id' => $childB->category_id,
            'supplier_id' => null,
            'name' => 'Solo en B',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 600,
            'purchase_price' => 60,
            'stock_current' => 1,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $this->get(route('clients.catalog', ['category_id' => $childA->category_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('summary.totalProducts', 1)
                ->where('products.0.name', 'Solo en A')
                ->where('products', fn ($products) => collect($products)->pluck('name')->doesntContain('Solo en B'))
            );
    }

    public function test_empty_category_shows_specific_message(): void
    {
        $emptyParent = Category::create([
            'name' => 'CF4 Vacía',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $this->get(route('clients.catalog', ['category_id' => $emptyParent->category_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('emptyCategoryNoProducts', true)
                ->where('summary.totalProducts', 0)
            );
    }
}
