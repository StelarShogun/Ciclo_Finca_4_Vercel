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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Genera HTML estático de todas las plantillas transaccionales (sin SMTP).
 *
 * Uso:
 *   GENERATE_EMAIL_PREVIEWS=1 php artisan test tests/Feature/GenerateEmailPreviewHtmlTest.php
 *
 * Abrir en el navegador:
 *   storage/app/email-previews/index.html
 *
 * Docker:
 *   docker exec laravel_app_ciclo env GENERATE_EMAIL_PREVIEWS=1 php artisan test tests/Feature/GenerateEmailPreviewHtmlTest.php
 */
class GenerateEmailPreviewHtmlTest extends TestCase
{
    use RefreshDatabase;

    private string $outputDir;

    private const LOGO_PUBLIC_PATH = 'assets/images/brand/logo-ciclo-finca-icon-64.png';

    private const LOGO_PREVIEW_RELATIVE = 'assets/logo-ciclo-finca-icon-64.png';

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }

        if (! filter_var(env('GENERATE_EMAIL_PREVIEWS', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped(
                'Omitido: define GENERATE_EMAIL_PREVIEWS=1 para escribir HTML en storage/app/email-previews/.'
            );
        }

        foreach (['sales', 'sale_items', 'client_table', 'admins', 'products'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Falta la tabla requerida: '.$table);
            }
        }

        $previewBaseUrl = rtrim((string) env('EMAIL_PREVIEW_APP_URL', 'http://127.0.0.1:8080'), '/');
        Config::set('app.url', $previewBaseUrl);
        Config::set('app.frontend_url', $previewBaseUrl);
        Config::set('mail.default', 'array');

        $this->outputDir = storage_path('app/email-previews');
    }

    public function test_generates_html_previews_for_all_transactional_emails(): void
    {
        File::ensureDirectoryExists($this->outputDir);

        [$sale, $client] = $this->createPreviewFixtures();

        $previews = [
            '01-order-expiry-reminder.html' => [
                'label' => 'Recordatorio de vencimiento',
                'html' => (new OrderExpiryReminderMail($sale, now()->addDay(), 'Cliente Preview'))->render(),
            ],
            '02-weekly-dashboard-report.html' => [
                'label' => 'Reporte semanal',
                'html' => (new WeeklyDashboardReportMail(
                    [
                        'periodSales' => 485000,
                        'periodSalesCount' => 12,
                        'lowStockCount' => 3,
                        'lowStockList' => collect(),
                        'totalProducts' => 128,
                        'totalCategories' => 18,
                        'totalSuppliers' => 7,
                        'salesByDay' => [
                            ['date' => '2026-05-19', 'total' => 52000],
                            ['date' => '2026-05-20', 'total' => 78000],
                            ['date' => '2026-05-21', 'total' => 61000],
                        ],
                        'productsByCategory' => collect([
                            ['categoria' => 'Bicicletas', 'total' => 42],
                            ['categoria' => 'Componentes', 'total' => 35],
                        ]),
                        'topProducts' => collect(),
                    ],
                    Carbon::parse('2026-05-19'),
                    Carbon::parse('2026-05-25'),
                ))->render(),
            ],
            '03-order-ready-to-pickup.html' => [
                'label' => 'Pedido listo para recoger',
                'html' => view('emails.order-ready-to-pickup', [
                    'sale' => $sale,
                    'clientName' => 'Cliente Preview',
                    'invoicesUrl' => config('app.frontend_url').'/clientes/facturas?tab=facturas',
                ])->render(),
            ],
            '04-order-completed.html' => [
                'label' => 'Pedido completado',
                'html' => view('emails.order-completed', [
                    'sale' => $sale,
                    'clientName' => 'Cliente Preview',
                    'historyUrl' => config('app.frontend_url').'/clientes/facturas?tab=historial',
                ])->render(),
            ],
            '05-order-cancelled-notification.html' => [
                'label' => 'Pedido cancelado',
                'html' => view('emails.order-cancelled-notification', [
                    'sale' => $sale,
                    'clientName' => 'Cliente Preview',
                    'reason' => 'Plazo de retiro vencido (preview)',
                    'cancelledAt' => now(),
                ])->render(),
            ],
            '06-product-review-reminder.html' => [
                'label' => 'Reseña de productos',
                'html' => (new ProductReviewReminderNotification($sale))
                    ->toMail($client)
                    ->render(),
            ],
            '07-order-ready-to-pickup-notification.html' => [
                'label' => 'Notificación — listo para recoger',
                'html' => (new OrderReadyToPickupNotification($sale))
                    ->toMail($client)
                    ->render(),
            ],
            '08-order-completed-notification.html' => [
                'label' => 'Notificación — pedido completado',
                'html' => (new OrderCompletedNotification($sale))
                    ->toMail($client)
                    ->render(),
            ],
            '09-order-cancelled-notification-mail.html' => [
                'label' => 'Notificación — pedido cancelado',
                'html' => (new OrderCancelledNotification($sale, 'Plazo vencido (preview)', now()))
                    ->toMail($client)
                    ->render(),
            ],
        ];

        $this->copyLogoForOfflinePreviews();

        $indexEntries = [];

        foreach ($previews as $filename => $preview) {
            $path = $this->outputDir.DIRECTORY_SEPARATOR.$filename;
            $html = $this->rewritePreviewHtmlForOfflineViewing($preview['html']);
            File::put($path, $html);
            $this->assertFileExists($path);
            $this->assertNotEmpty($html);
            $this->assertStringContainsString(self::LOGO_PREVIEW_RELATIVE, $html);

            $indexEntries[] = [
                'file' => $filename,
                'label' => $preview['label'],
            ];
        }

        $indexPath = $this->outputDir.DIRECTORY_SEPARATOR.'index.html';
        File::put($indexPath, $this->buildIndexHtml($indexEntries));
        $this->assertFileExists($indexPath);

        fwrite(STDERR, "\n  Email previews written to: {$this->outputDir}\n");
        fwrite(STDERR, "  Open: {$indexPath}\n\n");
    }

    private function copyLogoForOfflinePreviews(): void
    {
        $source = public_path(self::LOGO_PUBLIC_PATH);
        $this->assertFileExists($source, 'Logo PNG requerido en public/'.self::LOGO_PUBLIC_PATH);

        $targetDir = $this->outputDir.DIRECTORY_SEPARATOR.'assets';
        File::ensureDirectoryExists($targetDir);
        File::copy($source, $targetDir.DIRECTORY_SEPARATOR.'logo-ciclo-finca-icon-64.png');
    }

    /**
     * Las plantillas usan URL absolutas (correcto en correo real). En previews locales
     * (file://) apuntan a localhost sin servidor; reemplazamos por ruta relativa al PNG copiado.
     */
    private function rewritePreviewHtmlForOfflineViewing(string $html): string
    {
        $relative = self::LOGO_PREVIEW_RELATIVE;
        $escapedPublicPath = preg_quote(self::LOGO_PUBLIC_PATH, '#');

        $html = preg_replace(
            '#https?://[^"\'\s>]+/'.$escapedPublicPath.'#i',
            $relative,
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * @return array{0: Sale, 1: Client}
     */
    private function createPreviewFixtures(): array
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Preview',
            'second_surname' => null,
            'gmail' => 'admin-preview@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Preview',
            'second_surname' => null,
            'gmail' => 'cliente-preview@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Bicicleta urbana Preview',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 150000,
            'purchase_price' => 90000,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-PREVIEW-001',
            'client_id' => $client->user_id,
            'seller_admin_id' => $admin->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 150000,
            'iva' => 0,
            'discount' => 0,
            'total' => 150000,
            'order_source' => 'web_cart',
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 150000,
            'unit_discount' => 0,
            'total' => 150000,
        ]);

        $sale->load(['saleItems.product']);

        return [$sale, $client];
    }

    /**
     * @param  list<array{file: string, label: string}>  $entries
     */
    private function buildIndexHtml(array $entries): string
    {
        $generatedAt = now()->format('d/m/Y H:i:s');
        $listItems = '';

        foreach ($entries as $entry) {
            $file = htmlspecialchars($entry['file'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($entry['label'], ENT_QUOTES, 'UTF-8');
            $listItems .= <<<HTML
                <li>
                    <a href="{$file}" target="_blank" rel="noopener noreferrer">{$label}</a>
                    <span class="file">{$file}</span>
                </li>

            HTML;
        }

        return <<<HTML
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>CF4 — Vista previa de correos</title>
                <style>
                    body {
                        font-family: 'Segoe UI', Arial, sans-serif;
                        margin: 0;
                        padding: 32px 24px;
                        background: #DAF1DE;
                        color: #163832;
                    }
                    .wrap {
                        max-width: 720px;
                        margin: 0 auto;
                        background: #fff;
                        border: 1px solid #c8e6c9;
                        border-radius: 12px;
                        padding: 28px 32px;
                        box-shadow: 0 4px 16px rgba(5, 31, 32, 0.08);
                    }
                    h1 { margin: 0 0 8px; font-size: 1.5rem; color: #235347; }
                    p.meta { margin: 0 0 24px; color: #555; font-size: 0.95rem; }
                    ul { list-style: none; padding: 0; margin: 0; }
                    li {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: baseline;
                        gap: 8px 16px;
                        padding: 12px 0;
                        border-bottom: 1px solid #eef5ef;
                    }
                    li:last-child { border-bottom: none; }
                    a {
                        color: #235347;
                        font-weight: 600;
                        text-decoration: none;
                    }
                    a:hover { text-decoration: underline; }
                    .file {
                        font-size: 0.85rem;
                        color: #777;
                        font-family: ui-monospace, monospace;
                    }
                </style>
            </head>
            <body>
                <div class="wrap">
                    <h1>Ciclo Finca 4 — Plantillas de correo</h1>
                    <p class="meta">Generado: {$generatedAt}. Abre cada enlace en una pestaña nueva. El logo usa <code>assets/logo-ciclo-finca-icon-64.png</code> (sin servidor web).</p>
                    <ul>
                        {$listItems}
                    </ul>
                </div>
            </body>
            </html>
            HTML;
    }
}
