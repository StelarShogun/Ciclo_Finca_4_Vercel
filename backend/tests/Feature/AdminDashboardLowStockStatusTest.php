<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardLowStockStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_low_stock_table_shows_word_status_not_percentage(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Dash',
            'second_surname' => null,
            'gmail' => 'dash-status@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $category = Category::create([
            'name' => 'Repuestos',
            'parent_category_id' => null,
        ]);

        Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'Filtro Crítico',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 5000,
            'purchase_price' => 2000,
            'stock_current' => 1,
            'stock_minimum' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard/Index', false)
            ->where('lowStockList.0.name', 'Filtro Crítico')
            ->where('lowStockList.0.stock', 1)
        );
        $response->assertDontSee('>10%<', false);
    }
}
