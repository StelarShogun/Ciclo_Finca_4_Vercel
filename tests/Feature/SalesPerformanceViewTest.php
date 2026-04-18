<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPerformanceViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_sales_performance_page(): void
    {
        $this->get(route('admin.reports.sales-performance'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_sales_performance_page(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'View',
            'second_surname' => null,
            'gmail' => 'admin-sp-view@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.reports.sales-performance'))
            ->assertOk()
            ->assertSee('Desempeño de ventas', false);
    }
}
