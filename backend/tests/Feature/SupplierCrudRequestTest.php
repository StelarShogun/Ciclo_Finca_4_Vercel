<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupplierCrudRequestTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Supplier',
            'second_surname' => null,
            'gmail' => 'admin-supplier-crud@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Proveedor Seguro',
            'primary_contact' => 'Contacto Prueba',
            'phone' => '88889999',
            'email' => 'proveedor-seguro@example.com',
            'address' => 'San Jose, Costa Rica',
            'delivery_time' => 5,
            'rating' => 4.5,
        ], $overrides);
    }

    public function test_admin_can_create_supplier_with_form_request_validation(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('suppliers.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('suppliers', [
            'email' => 'proveedor-seguro@example.com',
        ]);
    }

    public function test_admin_can_update_supplier_with_form_request_validation(): void
    {
        $supplier = Supplier::create($this->payload());

        $this->actingAs($this->admin(), 'admin')
            ->putJson(route('suppliers.update', $supplier->supplier_id), $this->payload([
                'name' => 'Proveedor Actualizado',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('suppliers', [
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Proveedor Actualizado',
        ]);
    }
}
