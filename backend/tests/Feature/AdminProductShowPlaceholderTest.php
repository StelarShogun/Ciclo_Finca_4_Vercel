<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductShowPlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_show_json_includes_placeholder_icon_for_category(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-show-placeholder@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
        $root = Category::create([
            'name' => 'Bicicletas',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'MTB',
            'parent_category_id' => $root->category_id,
        ]);
        $product = Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'API Placeholder Bike',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100000,
            'purchase_price' => 50000,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('products.show', $product->product_id));

        $response->assertOk();
        $response->assertJsonPath('data.uses_placeholder_image', true);
        $response->assertJsonPath('data.placeholder_icon_class', 'fas fa-bicycle');
    }
}
