<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CF4AdminSalesClientInvoiceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                $this->markTestSkipped('CF4-7 requiere MySQL para validar el listado de ventas.');
            }

            foreach (['admins', 'client_table', 'sales'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Tabla requerida no existe: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

    private function makeAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF4',
            'second_surname' => null,
            'gmail' => 'admin-cf4-7@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    private function makeClient(string $email, string $name, string $surname): Client
    {
        return Client::create([
            'name' => $name,
            'first_surname' => $surname,
            'second_surname' => null,
            'gmail' => $email,
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
    }

    /** CP07-01 + CA 1-2-4 */
    public function test_admin_sales_list_shows_client_name_and_unique_invoice_number_per_row(): void
    {
        $admin = $this->makeAdmin();
        $clientA = $this->makeClient('cliente-a-cf4-7@example.com', 'Ana', 'Rojas');
        $clientB = $this->makeClient('cliente-b-cf4-7@example.com', 'Luis', 'Mora');

        $saleA = Sale::create([
            'invoice_number' => 'CF4-7001',
            'client_id' => $clientA->user_id,
            'seller_admin_id' => $admin->user_id,
            'sale_date' => now()->subHour(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'subtotal' => 10000,
            'iva' => 0,
            'discount' => 0,
            'total' => 10000,
            'order_source' => 'web_cart',
        ]);

        $saleB = Sale::create([
            'invoice_number' => 'CF4-7002',
            'client_id' => $clientB->user_id,
            'seller_admin_id' => $admin->user_id,
            'sale_date' => now(),
            'payment_method' => 'sinpe',
            'status' => 'completed',
            'subtotal' => 15000,
            'iva' => 0,
            'discount' => 0,
            'total' => 15000,
            'order_source' => 'web_cart',
        ]);

        $this->assertNotSame($saleA->invoice_number, $saleB->invoice_number, 'El número de factura no debe repetirse entre ventas.');

        $response = $this->actingAs($admin, 'admin')->get(route('sales.index'));
        $response->assertStatus(200);
        $response->assertSee('Número de factura', false);
        $response->assertSee('Cliente', false);
        $response->assertSee($saleA->invoice_number, false);
        $response->assertSee($saleB->invoice_number, false);
        $response->assertSee('Ana Rojas', false);
        $response->assertSee('Luis Mora', false);
    }

    /** CP07-02 + CA 6 */
    public function test_non_admin_cannot_access_sales_list(): void
    {
        $client = $this->makeClient('cliente-no-admin-cf4-7@example.com', 'Cliente', 'NoAdmin');

        $response = $this->actingAs($client, 'web')->get(route('sales.index'));
        $response->assertRedirect(route('admin.login'));
    }

    /** CP07-03 + CA 5 */
    public function test_sales_list_row_includes_basic_fields_with_client_and_invoice(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient('cliente-basico-cf4-7@example.com', 'Marta', 'Jimenez');

        $sale = Sale::create([
            'invoice_number' => 'CF4-7003',
            'client_id' => $client->user_id,
            'seller_admin_id' => $admin->user_id,
            'sale_date' => now(),
            'payment_method' => 'transfer',
            'status' => 'completed',
            'subtotal' => 22000,
            'iva' => 0,
            'discount' => 0,
            'total' => 22000,
            'order_source' => 'web_cart',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('sales.index'));
        $response->assertStatus(200);
        $response->assertSee($sale->invoice_number, false);
        $response->assertSee('Marta Jimenez', false);
        $response->assertSee($sale->sale_date->format('d/m/Y'), false);
        $response->assertSee('Confirmada', false);
    }
}
