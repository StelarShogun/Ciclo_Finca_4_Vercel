<?php

namespace Tests\Unit;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SuppliersIndexFlashAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_suppliers_index_renders_success_flash_for_sweetalert(): void
    {
        $this->withSession(['status' => 'Supplier deleted successfully.']);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Suppliers/Index', false)
                ->where('flash.status', 'Supplier deleted successfully.')
            );
    }

    public function test_suppliers_index_renders_error_flash_for_sweetalert(): void
    {
        $this->withSession(['error' => 'Supplier not found.']);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Suppliers/Index', false)
                ->where('flash.error', 'Supplier not found.')
            );
    }

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Suppliers',
            'gmail' => 'admin-suppliers-flash@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }
}
