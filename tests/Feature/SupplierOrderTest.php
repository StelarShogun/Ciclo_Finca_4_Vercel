<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('SupplierOrderTest requiere MySQL.');
        }

        foreach (['admins', 'client_table', 'orders', 'suppliers'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Tabla requerida no existe: '.$table);
            }
        }
    }

    private function authenticateAdmin(): AdminUser
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Web',
            'second_surname' => null,
            'gmail' => 'supplier-order-test-web@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'SupplierOrderTest',
            'second_surname' => null,
            'gmail' => 'supplier-order-test-admin@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($admin);

        return $admin;
    }

    public function test_supplier_orders_index_table_has_no_confirmation_column_header(): void
    {
        $this->authenticateAdmin();

        $supplier = Supplier::create([
            'name' => 'Proveedor Test SO',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'proveedor-so-test@example.com',
            'address' => 'Addr',
            'delivery_time' => 3,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-2026-9100',
            'estimated_delivery_date' => now()->addDays(3)->toDateString(),
            'date' => now(),
            'state' => 'confirmed',
            'total' => 1000,
        ]);

        $html = $this->get(route('admin.supplier-orders.index'))->assertStatus(200)->getContent();

        $this->assertStringNotContainsString('<th>Confirmación</th>', $html);
    }

    public function test_supplier_order_json_show_payload_has_no_confirmed_at(): void
    {
        $this->authenticateAdmin();

        $supplier = Supplier::create([
            'name' => 'Proveedor Test SO JSON',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'proveedor-so-json@example.com',
            'address' => 'Addr',
            'delivery_time' => 3,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        $order = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-2026-9101',
            'estimated_delivery_date' => now()->addDays(3)->toDateString(),
            'date' => now(),
            'state' => 'draft',
            'total' => 500,
        ]);

        $payload = $this->getJson('/supplier-orders/'.$order->num_order)
            ->assertOk()
            ->json('order');

        $this->assertIsArray($payload);
        $this->assertArrayNotHasKey('confirmed_at', $payload);
        $this->assertArrayNotHasKey('confirmed_by_label', $payload);
    }
}
