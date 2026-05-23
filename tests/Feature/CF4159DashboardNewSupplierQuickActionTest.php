<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CF4159DashboardNewSupplierQuickActionTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF4159',
            'second_surname' => null,
            'gmail' => 'admin-cf4159-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    public function test_dashboard_quick_action_links_to_supplier_create_route(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nuevo proveedor', false)
            ->assertSee(route('suppliers.create'), false)
            ->assertSee('action-card', false);
    }

    public function test_suppliers_create_redirects_to_index_with_open_new_query(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('suppliers.create'))
            ->assertRedirect(route('suppliers.index', ['open' => 'new']));
    }

    public function test_suppliers_index_exposes_new_supplier_form_modal(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('suppliers.index', ['open' => 'new']))
            ->assertOk()
            ->assertSee('id="new-supplier-modal"', false)
            ->assertSee('id="new-supplier-form"', false)
            ->assertSee('Nuevo proveedor', false);
    }

    public function test_guest_cannot_access_supplier_create_route(): void
    {
        $this->get(route('suppliers.create'))
            ->assertRedirect(route('admin.login'));
    }
}
