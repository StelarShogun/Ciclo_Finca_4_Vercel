<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogProductImagePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_category_icon_placeholder_for_product_without_image(): void
    {
        $root = Category::create([
            'name' => 'Bicicletas',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'MTB',
            'parent_category_id' => $root->category_id,
        ]);
        Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'Bike Sin Foto Catalog',
            'description' => 'Test',
            'image' => 'default.png',
            'sale_price' => 100000,
            'purchase_price' => 50000,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => true,
        ]);

        $response = $this->get(route('clients.catalog'));
        $response->assertOk();
        $response->assertSee('product-media-placeholder', false);
        $response->assertSee('fa-bicycle', false);
        $response->assertSee('Bike Sin Foto Catalog', false);
    }
}
