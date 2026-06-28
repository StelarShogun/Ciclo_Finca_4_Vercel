<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin suppliers: auth, listado filtrable y CRUD reusando SupplierService.
 */
class SuppliersApiTest extends TestCase
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
            ['gmail' => 'sup-admin@example.com'],
            ['name' => 'Sup', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Distribuidora Bici',
            'primary_contact' => 'Juan Perez',
            'phone' => '88887777',
            'email' => 'contacto@distribuidora.com',
            'address' => 'San José centro',
            'delivery_time' => 5,
            'rating' => 4.5,
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/suppliers')->assertStatus(401);
    }

    public function test_lists_and_filters(): void
    {
        $this->actingAs($this->admin(), 'admin');
        Supplier::create($this->payload(['name' => 'Alpha', 'email' => 'a@x.com']));
        Supplier::create($this->payload(['name' => 'Beta', 'email' => 'b@x.com']));

        $this->getJson('/api/v1/admin/suppliers')
            ->assertOk()
            ->assertJsonStructure(['data' => ['suppliers', 'averageRating', 'pagination', 'filters']]);

        $this->getJson('/api/v1/admin/suppliers?name=Alph')
            ->assertOk()
            ->assertJsonPath('data.suppliers.0.name', 'Alpha');
    }

    public function test_creates_supplier(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->postJson('/api/v1/admin/suppliers', $this->payload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Distribuidora Bici');

        $this->assertDatabaseHas('suppliers', ['email' => 'contacto@distribuidora.com']);
    }

    public function test_create_validates_unique_email(): void
    {
        $this->actingAs($this->admin(), 'admin');
        Supplier::create($this->payload());

        $this->postJson('/api/v1/admin/suppliers', $this->payload(['name' => 'Otro']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_updates_supplier(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $supplier = Supplier::create($this->payload());

        $this->putJson("/api/v1/admin/suppliers/{$supplier->supplier_id}", $this->payload(['name' => 'Nuevo Nombre']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nuevo Nombre');
    }

    public function test_deletes_supplier(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $supplier = Supplier::create($this->payload());

        $this->deleteJson("/api/v1/admin/suppliers/{$supplier->supplier_id}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
