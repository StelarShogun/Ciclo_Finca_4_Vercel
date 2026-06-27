<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminInventoryImagePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_shows_category_icon_placeholder_for_product_without_image(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-inv-placeholder@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
        $root = Category::create([
            'name' => 'Componentes',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'Transmisión',
            'parent_category_id' => $root->category_id,
        ]);
        Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'Grupo Sin Foto Admin',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100000,
            'purchase_price' => 50000,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('inventory'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Inventory/Index', false)
            ->where('products.0.name', 'Grupo Sin Foto Admin')
            ->where('products.0.uses_placeholder', true)
            ->where('products.0.placeholder_icon', 'fas fa-cogs')
        );
    }
}
