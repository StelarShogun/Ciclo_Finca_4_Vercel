<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * CF4 — Admin inventory product modals use shared file-upload UI.
 */
class CF4AdminInventoryFormUiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateAdmin(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Web',
            'second_surname' => null,
            'gmail' => 'cf4-inventory-ui-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $admin = AdminUser::create([
            'name' => 'Inventory',
            'first_surname' => 'UI',
            'second_surname' => null,
            'gmail' => 'cf4-inventory-ui-admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($admin);
    }

    public function test_inventory_page_includes_styled_file_upload_zones(): void
    {
        $this->authenticateAdmin();

        $category = Category::create([
            'name' => 'CF4 Inventory UI Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $supplier = Supplier::create([
            'name' => 'CF4 Inventory UI Sup',
            'primary_contact' => 'Contact',
            'phone' => '0000',
            'email' => 'cf4-inv-ui-'.uniqid().'@example.com',
            'address' => 'Addr',
            'delivery_time' => 1,
            'rating' => 5.0,
            'status' => 'active',
        ]);
        Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'CF4 Inventory UI Product',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $response = $this->get(route('inventory'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Inventory/Index', false)
            ->where('products.0.name', 'CF4 Inventory UI Product')
            ->where('categories.0.name', 'CF4 Inventory UI Cat')
            ->where('suppliers.0.name', 'CF4 Inventory UI Sup')
        );
    }
}
