<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * API v1 admin dashboard: payload (mismo shape que el ViewModel Inertia) y auth.
 */
class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
        Cache::flush();
    }

    private function admin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'dash-admin@example.com'],
            [
                'name' => 'Dash',
                'first_surname' => 'Admin',
                'second_surname' => null,
                'password' => bcrypt('password123'),
                'last_access' => now(),
            ]
        );
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
    }

    public function test_returns_dashboard_payload_for_admin(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->getJson('/api/v1/admin/dashboard?range=last7')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'totalProducts',
                    'totalSuppliers',
                    'totalCategories',
                    'todaySales',
                    'salesTrend',
                    'monthlySales',
                    'monthlyTrend',
                    'salesByDay',
                    'salesRange',
                    'recentSales',
                    'productsByCategory',
                    'topProducts',
                ],
            ])
            ->assertJsonPath('data.salesRange', 'last7')
            ->assertJsonCount(7, 'data.salesByDay');
    }
}
