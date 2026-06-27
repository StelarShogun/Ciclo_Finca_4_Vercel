<?php

namespace Tests\Feature\Api;

use App\Actions\Admin\Products\ListProducts;
use App\Models\AdminUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * API v1 admin products (lista). Cubre auth y que ListProducts devuelve un
 * paginador (regresión: antes declaraba ': array' y tiraba TypeError).
 */
class ProductsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function admin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'prod-admin@example.com'],
            [
                'name' => 'Prod',
                'first_surname' => 'Admin',
                'second_surname' => null,
                'password' => bcrypt('password123'),
                'last_access' => now(),
            ]
        );
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/products')->assertStatus(401);
    }

    public function test_returns_paginated_products_for_admin(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->getJson('/api/v1/admin/products')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_list_products_action_returns_paginator(): void
    {
        $paginator = app(ListProducts::class)->handle(Request::create('/'));

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
    }
}
