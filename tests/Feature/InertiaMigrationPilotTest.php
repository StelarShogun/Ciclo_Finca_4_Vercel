<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaMigrationPilotTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_is_rendered_by_inertia(): void
    {
        $this->get(route('clients.legal.terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Legal/Terms', false)
                ->where('legalTitle', 'Términos y condiciones')
                ->where('legalUpdated', 'mayo 2026')
            );
    }

    public function test_admin_dashboard_inertia_pilot_requires_admin_auth(): void
    {
        $this->get(route('dashboard.inertia-pilot'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_dashboard_inertia_pilot_renders_for_admin(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Inertia',
            'second_surname' => null,
            'gmail' => 'admin-inertia@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('dashboard.inertia-pilot'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/Index', false)
                ->has('totalProducts')
                ->has('todaySales')
            );
    }
}
