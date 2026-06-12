<?php

namespace Tests\Feature;

use App\Mail\OrderExpiryReminderMail;
use App\Mail\WeeklyDashboardReportMail;
use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderReadyToPickupNotification;
use App\Notifications\ProductReviewReminderNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * CF4-169 — Plantilla base reutilizable para correos transaccionales.
 */
class CF4169TransactionalEmailLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mail.default', 'array');
        Config::set('app.url', 'https://cf4.example.test');
        Config::set('app.frontend_url', 'https://cf4.example.test');
    }

    public function test_transactional_emails_use_shared_base_layout(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF169',
            'second_surname' => null,
            'gmail' => 'admin-cf169@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Layout',
            'second_surname' => null,
            'gmail' => 'cliente-cf169@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'CF169 Producto',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-CF169',
            'client_id' => $client->user_id,
            'seller_admin_id' => $admin->user_id,
            'sale_date' => now(),
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
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 100,
            'unit_discount' => 0,
            'total' => 100,
        ]);

        $sale->load(['saleItems.product']);

        $expiresAt = now()->addDay();

        $samples = [
            (new OrderExpiryReminderMail($sale, $expiresAt, 'Cliente Layout'))->render(),
            (new WeeklyDashboardReportMail(
                [
                    'periodSales' => 0,
                    'periodSalesCount' => 0,
                    'lowStockCount' => 0,
                    'lowStockList' => collect(),
                    'totalProducts' => 0,
                    'totalCategories' => 0,
                    'totalSuppliers' => 0,
                    'salesByDay' => [],
                    'productsByCategory' => collect(),
                    'topProducts' => collect(),
                ],
                Carbon::parse('2026-05-01'),
                Carbon::parse('2026-05-07'),
            ))->render(),
            view('emails.order-ready-to-pickup', [
                'sale' => $sale,
                'clientName' => 'Cliente Layout',
                'invoicesUrl' => 'https://cf4.example.test/facturas',
            ])->render(),
            view('emails.order-completed', [
                'sale' => $sale,
                'clientName' => 'Cliente Layout',
                'historyUrl' => 'https://cf4.example.test/historial',
            ])->render(),
            view('emails.order-cancelled-notification', [
                'sale' => $sale,
                'clientName' => 'Cliente Layout',
                'reason' => 'Plazo vencido',
                'cancelledAt' => now(),
            ])->render(),
            (new ProductReviewReminderNotification($sale))
                ->toMail($client)
                ->render(),
            (new OrderReadyToPickupNotification($sale))
                ->toMail($client)
                ->render(),
            (new OrderCompletedNotification($sale))
                ->toMail($client)
                ->render(),
            (new OrderCancelledNotification($sale, 'Plazo vencido', now()))
                ->toMail($client)
                ->render(),
        ];

        foreach ($samples as $html) {
            $this->assertEmailUsesBaseLayout($html);
        }
    }

    private function assertEmailUsesBaseLayout(string $html): void
    {
        $this->assertStringContainsString('#DAF1DE', $html);
        $this->assertStringContainsString('#235347', $html);
        $this->assertStringContainsString('CICLO', $html);
        $this->assertStringContainsString('FINCA', $html);
        $this->assertStringContainsString('Contacto Ciclo Finca 4', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('max-width:620px', $html);
    }
}
