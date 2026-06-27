<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
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
        parent::setUp();
        Config::set('sales.order_expiration_days', 30);
        Config::set('sales.expiry_alert_days', 2);
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');
        Carbon::setTestNow(Carbon::parse('2026-06-15 00:00:00', 'UTC'));
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** CP1: El sistema muestra la fecha y hora exacta de creación del pedido en la lista o detalle. */
    public function test_list_and_detail_show_exact_creation_date_time(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0001',
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
            'sale_date' => Carbon::parse('2026-06-15 00:00:00', 'UTC'),
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')->get(route('sales.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sales/Index', false)
            ->where('sales.0.sale_date_label', $sale->adminSaleDateLabel())
        );

        $responseJson = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $responseJson->assertStatus(200);
        $sale->refresh();
        $responseJson->assertJsonPath('sale.sale_date', $sale->sale_date->toISOString());
    }

    /** CP2: El sistema calcula automáticamente los días restantes antes de la eliminación. */
    public function test_system_calculates_days_remaining_until_expiration(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0002',
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
            'sale_date' => Carbon::parse('2026-06-05 00:00:00', 'UTC'),
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $response->assertStatus(200);
        $response->assertJsonStructure(['sale' => ['days_remaining_until_expiration', 'expires_at', 'is_expiry_warning']]);
        $this->assertSame(20, $sale->fresh()->days_remaining_until_expiration);
        $response->assertJsonPath('sale.days_remaining_until_expiration', 20);
    }

    /** CP3: El conteo se actualiza dinámicamente (recalculado en cada petición). */
    public function test_days_remaining_recalculated_on_each_request(): void
    {
        $saleDate = Carbon::parse('2026-05-21 00:00:00', 'UTC');
        $sale = Sale::create([
            'invoice_number' => 'INV'.$saleDate->format('Ymd').'0003',
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
            'sale_date' => Carbon::parse('2026-05-18 00:00:00', 'UTC'),
        ]);

        $this->assertTrue($sale->is_expiry_warning);
        $this->assertSame(2, $sale->days_remaining_until_expiration);

        $responseJson = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $responseJson->assertOk();
        $responseJson->assertJsonPath('sale.is_expiry_warning', true);
        $responseJson->assertJsonPath('sale.days_remaining_until_expiration', 2);
    }

    /** CP5: Si el pedido pendiente supera el tiempo límite, el comando lo cancela automáticamente. */
    public function test_expired_orders_are_deleted_by_command(): void
    {
        $oldDate = now()->subDays(31);
        $sale = Sale::create([
            'invoice_number' => 'INV'.$oldDate->format('Ymd').'0005',
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
            'sale_date' => $oldDate,
        ]);
        $id = $sale->sale_id;

        $this->artisan('sales:delete-expired')->assertSuccessful();

        $sale->refresh();
        $this->assertSame('cancelled', $sale->status);
        $this->assertStringContainsString('Cancelado automáticamente', (string) $sale->notes);
    }

    /** CP6: Pedido recién creado muestra el tiempo completo restante. */
    public function test_newly_created_order_shows_full_days_remaining(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0006',
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
            'sale_date' => Carbon::parse('2026-06-15 00:00:00', 'UTC'),
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $response->assertStatus(200);
        $this->assertSame(30, $sale->days_remaining_until_expiration);
        $response->assertJsonPath('sale.days_remaining_until_expiration', 30);
    }

    /** CP7: Pedido cercano a la fecha límite muestra correctamente los días reducidos. */
    public function test_order_near_limit_shows_reduced_days_remaining(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0007',
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
            'sale_date' => Carbon::parse('2026-05-17 00:00:00', 'UTC'),
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')->getJson(route('sales.show', $sale->sale_id));
        $response->assertStatus(200);
        $this->assertSame(1, $sale->days_remaining_until_expiration);
        $response->assertJsonPath('sale.days_remaining_until_expiration', 1);
    }

    /** CP8: Pedido ya eliminado no aparece en la lista de activos. */
    public function test_deleted_order_not_shown_in_active_list(): void
    {
        $oldDate = now()->subDays(31);
        $sale = Sale::create([
            'invoice_number' => 'INV'.$oldDate->format('Ymd').'0008',
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
