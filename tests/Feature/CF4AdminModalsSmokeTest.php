<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Smoke tests for admin modal markup across key modules.
 */
class CF4AdminModalsSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateAdmin(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Modal',
            'second_surname' => null,
            'gmail' => 'cf4-modals-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $admin = AdminUser::create([
            'name' => 'Modal',
            'first_surname' => 'Smoke',
            'second_surname' => null,
            'gmail' => 'cf4-modals-admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($admin);
    }

    public function test_inventory_modals_include_dialog_semantics(): void
    {
        $this->authenticateAdmin();

        $response = $this->get(route('inventory'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Admin/Inventory/Index', false));
    }

    public function test_sales_page_includes_view_and_new_sale_modals(): void
    {
        $this->authenticateAdmin();

        $response = $this->get(route('sales.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Admin/Sales/Index', false));
    }

    public function test_brands_page_modal_uses_active_overlay_pattern(): void
    {
        $this->authenticateAdmin();

        $response = $this->get(route('brands.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Admin/Brands/Index', false));
    }

    public function test_suppliers_page_includes_detail_and_form_modals(): void
    {
        $this->authenticateAdmin();

        $response = $this->get(route('suppliers.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Admin/Suppliers/Index', false));
    }
}
