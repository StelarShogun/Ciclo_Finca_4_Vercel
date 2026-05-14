<?php

namespace Tests\Feature;

use App\Mail\OrderExpiryReminderMail;
use App\Models\AdminUser;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-94 — Hitos post-compra por correo (recordatorio de caducidad, cancelación automática).
 *
 * Casos unificados:
 * - CP94-01 / CA: correo de recordatorio con datos coherentes al Sale destinatario.
 * - CP94-02 / CA 1: dos clientes; cada correo no mezcla ítems del otro pedido.
 * - CP94-03 / CA 2–3: comandos `sales:send-expiry-reminders` y `sales:delete-expired` sin errores;
 *   cancelación registra envío de correo en order_notification_logs (Mailhog/colarray en dev).
 *
 * Precondición: PHPUnit usa SQLite en memoria por defecto (`phpunit.xml`). Correo: driver `array`.
 *
 * Comando (no ejecutar desde CI del agente; usar en tu máquina / Docker):
 *   php artisan test tests/Feature/CF494WebOrderMilestoneMailTest.php
 */
class CF494WebOrderMilestoneMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }

        foreach (['sales', 'sale_items', 'client_table', 'admins', 'products', 'order_notification_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Falta la tabla requerida: '.$table);
            }
        }

        Config::set('mail.default', 'array');
        Config::set('sales.order_expiration_days', 30);
        Cache::forget(AppSetting::cacheKeyOrderExpirationDays());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cf94_reminder_mails_per_recipient_and_auto_cancel_mail_channel(): void
    {
        // Ventana fija: recordatorio para pedidos en el último día del plazo (D = 30).
        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', config('app.timezone')));

        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF94',
            'second_surname' => null,
            'gmail' => 'admin-cf94@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $clientAlpha = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Alpha',
            'second_surname' => null,
            'gmail' => 'cf94-alpha@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $clientBeta = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Beta',
            'second_surname' => null,
            'gmail' => 'cf94-beta@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $productAlpha = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'CF94 Producto Solo Alpha',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $productBeta = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'CF94 Producto Solo Beta',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 200,
            'purchase_price' => 100,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        // Dentro de [now - 30d, now - 29d) respecto a 2026-06-15 12:00.
        $saleDateInReminderWindow = Carbon::parse('2026-06-15 12:00:00')->subDays(29)->subHours(2);

        $saleAlpha = Sale::create([
            'invoice_number' => 'CF4-CF94-A',
            'client_id' => $clientAlpha->user_id,
            'seller_admin_id' => $admin->user_id,
            'sale_date' => $saleDateInReminderWindow,
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 100,
            'iva' => 0,
            'discount' => 0,
            'total' => 100,
            'order_source' => 'web_cart',
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $saleAlpha->sale_id,
            'product_id' => $productAlpha->product_id,
            'quantity' => 1,
            'unit_price' => 100,
            'unit_discount' => 0,
            'total' => 100,
        ]);

        $saleBeta = Sale::create([
            'invoice_number' => 'CF4-CF94-B',
            'client_id' => $clientBeta->user_id,
            'seller_admin_id' => $admin->user_id,
            'sale_date' => $saleDateInReminderWindow->copy()->addHour(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 200,
            'iva' => 0,
            'discount' => 0,
            'total' => 200,
            'order_source' => 'web_cart',
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $saleBeta->sale_id,
            'product_id' => $productBeta->product_id,
            'quantity' => 1,
            'unit_price' => 200,
            'unit_discount' => 0,
            'total' => 200,
        ]);

        Mail::fake();

        $this->artisan('sales:send-expiry-reminders')->assertSuccessful();

        Mail::assertSent(OrderExpiryReminderMail::class, 2);

        Mail::assertSent(OrderExpiryReminderMail::class, function (OrderExpiryReminderMail $mail) use (
            $saleAlpha,
            $clientAlpha,
        ): bool {
            if ((int) $mail->sale->sale_id !== (int) $saleAlpha->sale_id) {
                return false;
            }

            $html = $mail->render();

            return $mail->hasTo($clientAlpha->gmail)
                && str_contains($html, 'CF94 Producto Solo Alpha')
                && ! str_contains($html, 'CF94 Producto Solo Beta')
                && str_contains((string) $mail->envelope()->subject, (string) $saleAlpha->sale_id);
        });

        Mail::assertSent(OrderExpiryReminderMail::class, function (OrderExpiryReminderMail $mail) use (
            $saleBeta,
            $clientBeta,
        ): bool {
            if ((int) $mail->sale->sale_id !== (int) $saleBeta->sale_id) {
                return false;
            }

            $html = $mail->render();

            return $mail->hasTo($clientBeta->gmail)
                && str_contains($html, 'CF94 Producto Solo Beta')
                && ! str_contains($html, 'CF94 Producto Solo Alpha')
                && str_contains((string) $mail->envelope()->subject, (string) $saleBeta->sale_id);
        });

        // --- CP94-03: cancelación automática por plazo; canal mail registrado (Sale / email del cliente).
        $saleExpired = Sale::create([
            'invoice_number' => 'CF4-CF94-EXP',
            'client_id' => $clientAlpha->user_id,
            'seller_admin_id' => $admin->user_id,
            'sale_date' => Carbon::parse('2026-06-15 12:00:00')->subDays(35),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 50,
            'iva' => 0,
            'discount' => 0,
            'total' => 50,
            'order_source' => 'web_cart',
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $saleExpired->sale_id,
            'product_id' => $productAlpha->product_id,
            'quantity' => 1,
            'unit_price' => 50,
            'unit_discount' => 0,
            'total' => 50,
        ]);

        $this->artisan('sales:delete-expired')->assertSuccessful();

        $saleExpired->refresh();
        $this->assertSame('cancelled', $saleExpired->status);

        $this->assertDatabaseHas('order_notification_logs', [
            'sale_id' => $saleExpired->sale_id,
            'client_id' => $clientAlpha->user_id,
            'channel' => 'mail',
            'status' => 'sent',
        ]);
    }
}
