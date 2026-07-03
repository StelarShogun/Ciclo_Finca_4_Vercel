<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin products: form-options y validación de creación.
 * El happy-path de create/update (con categoría/proveedor/marca reales) queda
 * cubierto por la prueba manual con curl; aquí se valida cableado, auth y reglas.
 */
class ProductFormApiTest extends TestCase
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
            ['gmail' => 'prodform-admin@example.com'],
            [
                'name' => 'Form',
                'first_surname' => 'Admin',
                'second_surname' => null,
                'password' => bcrypt('password123'),
                'last_access' => now(),
            ]
        );
    }

    public function test_form_options_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/products/form-options')->assertStatus(401);
    }

    public function test_form_options_returns_reference_data(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->getJson('/api/v1/admin/products/form-options')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['categories', 'subcategoriesByParent', 'brands', 'suppliers', 'statuses'],
            ])
            ->assertJsonPath('data.statuses.0.value', 'active');
    }

    public function test_store_rejects_invalid_payload(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->postJson('/api/v1/admin/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'category_id',
                'parent_category_id',
                'supplier_id',
                'brand_id',
                'name',
                'sale_price',
                'purchase_price',
            ]);
    }
}
