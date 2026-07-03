<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardImagePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_low_stock_table_shows_category_placeholder(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Dash',
            'second_surname' => null,
            'gmail' => 'dash-placeholder@example.com',
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
            'name' => 'Grupo Bajo Stock Sin Foto',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100000,
            'purchase_price' => 50000,
            'stock_current' => 2,
            'stock_minimum' => 5,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard/Index', false)
            ->where('lowStockList.0.name', 'Grupo Bajo Stock Sin Foto')
            ->where('lowStockList.0.category', 'Transmisión')
        );
    }
}
