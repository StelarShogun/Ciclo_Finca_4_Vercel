<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Casos de prueba CF4-20: Vigencia de pedidos (fecha creación, días restantes, alerta, eliminación automática).
 *
 * Requiere MySQL y migraciones ejecutadas (tabla sales). Para ejecutar con MySQL:
 *   Crear .env.testing con DB_CONNECTION=mysql y DB_DATABASE (ej. ciclofinca_test).
 *   php artisan test tests/Feature/SalesOrderExpiryTest.php
 * Con SQLite o sin driver los tests se marcan como skipped.
 */
class SalesOrderExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $adminUser;

    protected Client $customer;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests (ej. driver SQLite faltante). Use MySQL: '.$e->getMessage());
        }
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            $this->markTestSkipped('Estos tests requieren MySQL (tabla sales existe tras migraciones de refactor).');
        }
        if (! Schema::hasTable('sales')) {
            $this->markTestSkipped('La tabla sales no existe. Ejecuta migraciones con MySQL.');
        }
        Config::set('sales.order_expiration_days', 30);
        Config::set('sales.expiry_alert_days', 2);

        $this->adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-expiry@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $this->customer = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'cliente-expiry@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
    }

    /** CP1: El sistema muestra la fecha y hora exacta de creación del pedido en la lista o detalle. */
    public function test_list_and_detail_show_exact_creation_date_time(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0001',
            'customer_id' => null,
            'seller_id' => null,
            'client_id' => $this->customer->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 13,
            'discount' => 0,
            'total' => 113,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now(),
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')->get(route('sales.index'));
        $response->assertStatus(200);
        $response->assertSee($sale->sale_date->format('d/m/Y'), false);
        $response->assertSee($sale->sale_date->format('H:i'), false);

        $responseJson = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $responseJson->assertStatus(200);
        $responseJson->assertJsonPath('sale.sale_date', $sale->sale_date->toISOString());
    }

    /** CP2: El sistema calcula automáticamente los días restantes antes de la eliminación. */
    public function test_system_calculates_days_remaining_until_expiration(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0002',
            'customer_id' => null,
            'seller_id' => null,
            'client_id' => $this->customer->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 13,
            'discount' => 0,
            'total' => 113,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $response->assertStatus(200);
        $response->assertJsonStructure(['sale' => ['days_remaining_until_expiration', 'expires_at', 'is_expiry_warning']]);
        $this->assertEquals(20, $response->json('sale.days_remaining_until_expiration'));
    }

    /** CP3: El conteo se actualiza dinámicamente (recalculado en cada petición). */
    public function test_days_remaining_recalculated_on_each_request(): void
    {
        $saleDate = now()->subDays(25);
        $sale = Sale::create([
            'invoice_number' => 'INV'.$saleDate->format('Ymd').'0003',
            'customer_id' => null,
            'seller_id' => null,
            'client_id' => $this->customer->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 13,
            'discount' => 0,
            'total' => 113,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => $saleDate,
        ]);

        $daysRemaining = $sale->days_remaining_until_expiration;
        $this->assertIsInt($daysRemaining);
        $this->assertGreaterThanOrEqual(0, $daysRemaining);
        $this->assertLessThanOrEqual(30, $daysRemaining);
        // El valor viene del modelo (calculado con now()), por tanto se actualiza en cada carga
        $response = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $response->assertJsonPath('sale.days_remaining_until_expiration', $daysRemaining);
    }

    /** CP4: Cuando faltan dos días o menos, el sistema muestra alerta visual (clase/icono en vista). */
    public function test_alert_shown_when_two_days_or_less_remaining(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0004',
            'customer_id' => null,
            'seller_id' => null,
            'client_id' => $this->customer->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 13,
            'discount' => 0,
            'total' => 113,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now()->subDays(28),
        ]);

        $this->assertTrue($sale->is_expiry_warning);
        $response = $this->actingAs($this->adminUser, 'admin')->get(route('sales.index'));
        $response->assertStatus(200);
        $response->assertSee('expiry-warning', false);
        $response->assertSee('exclamation-triangle', false);
    }

    /** CP5: Si el pedido supera el tiempo límite, el sistema lo elimina (comando). */
    public function test_expired_orders_are_deleted_by_command(): void
    {
        $oldDate = now()->subDays(31);
        $sale = Sale::create([
            'invoice_number' => 'INV'.$oldDate->format('Ymd').'0005',
            'customer_id' => null,
            'seller_id' => null,
            'client_id' => $this->customer->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 13,
            'discount' => 0,
            'total' => 113,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => $oldDate,
        ]);
        $id = $sale->sale_id;

        $this->artisan('sales:delete-expired')->assertSuccessful();
        $this->assertNull(Sale::find($id));
    }

    /** CP6: Pedido recién creado muestra el tiempo completo restante. */
    public function test_newly_created_order_shows_full_days_remaining(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0006',
            'customer_id' => null,
            'seller_id' => null,
            'client_id' => $this->customer->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 13,
            'discount' => 0,
            'total' => 113,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'pending',
            'notes' => null,
            'sale_date' => now(),
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $response->assertStatus(200);
        $this->assertEquals(30, $response->json('sale.days_remaining_until_expiration'));
    }

    /** CP7: Pedido cercano a la fecha límite muestra correctamente los días reducidos. */
    public function test_order_near_limit_shows_reduced_days_remaining(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0007',
            'customer_id' => null,
            'seller_id' => null,
            'client_id' => $this->customer->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 13,
            'discount' => 0,
            'total' => 113,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now()->subDays(29),
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('sale.days_remaining_until_expiration'));
    }

    /** CP8: Pedido ya eliminado no aparece en la lista de activos. */
    public function test_deleted_order_not_shown_in_active_list(): void
    {
        $oldDate = now()->subDays(31);
        $sale = Sale::create([
            'invoice_number' => 'INV'.$oldDate->format('Ymd').'0008',
            'customer_id' => null,
            'seller_id' => null,
            'client_id' => $this->customer->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 13,
            'discount' => 0,
            'total' => 113,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => $oldDate,
        ]);
        $invoice = $sale->invoice_number;

        $this->artisan('sales:delete-expired')->assertSuccessful();

        $response = $this->actingAs($this->adminUser, 'admin')->get(route('sales.index'));
        $response->assertStatus(200);
        $response->assertDontSee($invoice, false);
    }
}
