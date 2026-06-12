<?php

namespace Tests\Feature;

use App\Mail\WeeklyDashboardReportMail;
use App\Models\AdminUser;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

/** Weekly dashboard KPI report. */
class CF4101SendWeeklyDashboardReportTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Persists the weekly report settings. */
    private function configureReport(
        int $day = 1,
        int $hour = 8,
        int $minute = 0,
        array $recipients = ['admin@ciclofinca.com']
    ): void {
        AppSetting::setWeeklyReportDay($day);
        AppSetting::setWeeklyReportHour($hour);
        AppSetting::setWeeklyReportMinute($minute);
        AppSetting::setWeeklyReportRecipients($recipients);
    }

    /** Creates a test supplier with a unique name. */
    private function createSupplier(): Supplier
    {
        static $counter = 0;
        $counter++;

        return Supplier::create([
            'name' => "Test Supplier {$counter}",
            'email' => "supplier{$counter}@test.com",
        ]);
    }

    /** Creates an active product linked to the given supplier. */
    private function createProduct(Supplier $supplier, int $stockCurrent = 20, int $stockMinimum = 5): Product
    {
        static $counter = 0;
        $counter++;

        return Product::create([
            'name' => "Test Product {$counter}",
            'supplier_id' => $supplier->supplier_id,
            'stock_current' => $stockCurrent,
            'stock_minimum' => $stockMinimum,
            'sale_price' => 1500.00,
            'purchase_price' => 900.00,
            'status' => 'active',
        ]);
    }

    /**
     * Creates a completed sale at midnight of the given date.
     * Using startOfDay() ensures the timestamp falls within the period range
     * used by buildKpis(), which sets $periodEnd = Carbon::now()->startOfDay().
     */
    private function createCompletedSale(Carbon $date, float $total = 10000.0): Sale
    {
        static $counter = 0;
        $counter++;

        return Sale::create([
            'invoice_number' => 'INV-TEST-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'sale_date' => $date->copy()->startOfDay(),
            'status' => 'completed',
            'payment_method' => 'cash',
            'subtotal' => $total,
            'total' => $total,
        ]);
    }

    /** Returns the test admin user, creating it if it does not exist. */
    private function createAdmin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'admin@cicloperez.com'],
            [
                'name' => 'Administrator',
                'first_surname' => 'System',
                'second_surname' => null,
                'password' => bcrypt('Admin2024!@#'),
                'last_access' => null,
            ]
        );
    }

    /** Extracts the KPIs array from the mailable via reflection. */
    private function getKpisFromMail(WeeklyDashboardReportMail $mail): array
    {
        $property = (new \ReflectionClass($mail))->getProperty('kpis');
        $property->setAccessible(true);

        return $property->getValue($mail);
    }

    /** Builds the base settings payload for the weekly report endpoint. */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'weekly_report_day' => 3,
            'weekly_report_hour' => 9,
            'weekly_report_minute' => 30,
            'weekly_report_recipients' => 'manager@ciclofinca.com, admin@ciclofinca.com',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Automatic dispatch
    // -------------------------------------------------------------------------

    /** One email is sent per configured recipient. */
    public function test_command_sends_email_to_all_recipients_at_configured_time(): void
    {
        Mail::fake();

        $recipients = ['manager@ciclofinca.com', 'admin@ciclofinca.com'];
        $now = Carbon::now();

        $this->configureReport(
            day: (int) $now->format('w'),
            hour: (int) $now->format('G'),
            minute: (int) $now->format('i'),
            recipients: $recipients,
        );

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        Mail::assertSentCount(count($recipients));

        foreach ($recipients as $email) {
            Mail::assertSent(WeeklyDashboardReportMail::class, fn ($mail) => $mail->hasTo($email));
        }
    }

    /** With no recipients configured, the command exits cleanly without sending anything. */
    public function test_command_aborts_gracefully_when_no_recipients_configured(): void
    {
        Mail::fake();

        AppSetting::setWeeklyReportRecipients([]);

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    /** Outside the configured schedule and without --force, no emails are sent. */
    public function test_command_does_not_send_outside_configured_time(): void
    {
        Mail::fake();

        $tomorrow = Carbon::now()->addDay();
        $this->configureReport(
            day: (int) $tomorrow->format('w'),
            hour: 3,
            minute: 0,
        );

        $this->artisan('reports:send-weekly-dashboard')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    // -------------------------------------------------------------------------
    // Recipient and schedule configuration
    // -------------------------------------------------------------------------

    /** The settings endpoint persists day, hour, minute, and recipients correctly. */
    public function test_settings_endpoint_persists_day_hour_minute_and_recipients(): void
    {
        $this->actingAs($this->createAdmin(), 'admin')
            ->putJson(
                route('admin.orders.settings.weekly-report.update'),
                $this->settingsPayload()
            )->assertOk()
            ->assertJsonFragment([
                'weekly_report_day' => 3,
                'weekly_report_hour' => 9,
                'weekly_report_minute' => 30,
            ]);

        $this->assertSame(3, AppSetting::getWeeklyReportDay());
        $this->assertSame(9, AppSetting::getWeeklyReportHour());
        $this->assertSame(30, AppSetting::getWeeklyReportMinute());
        $this->assertContains('manager@ciclofinca.com', AppSetting::getWeeklyReportRecipients());
        $this->assertContains('admin@ciclofinca.com', AppSetting::getWeeklyReportRecipients());
    }

    /** The settings endpoint rejects the request when all provided emails are invalid. */
    public function test_settings_endpoint_rejects_invalid_email_recipients(): void
    {
        $this->actingAs($this->createAdmin(), 'admin')
            ->putJson(
                route('admin.orders.settings.weekly-report.update'),
                $this->settingsPayload(['weekly_report_recipients' => 'not-an-email, neither-this'])
            )->assertStatus(422);
    }

    /** The dynamically built cron expression reflects the values stored in AppSetting. */
    public function test_schedule_cron_expression_matches_configured_settings(): void
    {
        $this->configureReport(day: 2, hour: 10, minute: 15);

        $cron = sprintf(
            '%d %d * * %d',
            AppSetting::getWeeklyReportMinute(),
            AppSetting::getWeeklyReportHour(),
            AppSetting::getWeeklyReportDay()
        );

        $this->assertSame('15 10 * * 2', $cron);
    }

    // -------------------------------------------------------------------------
    // Report content
    // -------------------------------------------------------------------------

    /**
     * The report includes the total sales for the period, excluding sales older than 7 days.
     *
     * CORRECCIÓN: En lugar de comparar contra límites hardcodeados (que fallan cuando
     * existen ventas previas en la base de datos), se calcula el total esperado con la
     * misma consulta que usa buildKpis() ANTES de ejecutar el comando. Así cualquier
     * dato pre-existente queda incluido en ambos lados de la comparación y se cancela.
     * La venta de hace 10 días sigue siendo excluida implícitamente: si el comando la
     * incluyera, el resultado sería expectedTotal + 99999, lo que haría fallar assertSame.
     */
    public function test_report_includes_period_sales_total_excluding_older_sales(): void
    {
        Mail::fake();

        $this->configureReport(recipients: ['test@ciclofinca.com']);

        $this->createCompletedSale(Carbon::now()->subDays(3), total: 25000.0);
        $this->createCompletedSale(Carbon::now()->subDays(10), total: 99999.0); // fuera del período

        // Calcular el total esperado ANTES de ejecutar el comando, usando la misma
        // ventana temporal que buildKpis(). Esto hace que cualquier venta pre-existente
        // quede reflejada en ambos lados y no rompa la aserción.
        $expectedTotal = Sale::whereBetween('sale_date', [
            Carbon::now()->startOfDay()->subDays(6),
            Carbon::now()->startOfDay(),
        ])->where('status', 'completed')->sum('total');

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        Mail::assertSent(WeeklyDashboardReportMail::class);

        $sentMail = Mail::sent(WeeklyDashboardReportMail::class)->first();
        $kpis = $this->getKpisFromMail($sentMail);

        // El KPI debe coincidir exactamente con la consulta directa a la BD.
        $this->assertSame((float) $expectedTotal, (float) $kpis['periodSales']);
    }

    /** The report reflects the count of products below minimum stock. */
    public function test_report_includes_low_stock_count(): void
    {
        Mail::fake();

        $this->configureReport(recipients: ['test@ciclofinca.com']);

        $supplier = $this->createSupplier();
        $this->createProduct($supplier, stockCurrent: 2, stockMinimum: 10);  // low stock
        $this->createProduct($supplier, stockCurrent: 50, stockMinimum: 5);  // stock OK

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        Mail::assertSent(WeeklyDashboardReportMail::class, function ($mail) {
            return $this->getKpisFromMail($mail)['lowStockCount'] >= 1;
        });
    }

    /** The report includes the distribution of active products by category. */
    public function test_report_includes_products_by_category(): void
    {
        Mail::fake();

        $this->configureReport(recipients: ['test@ciclofinca.com']);

        Category::create(['name' => 'Test Category']);
        $this->createProduct($this->createSupplier());

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        Mail::assertSent(WeeklyDashboardReportMail::class, function ($mail) {
            return ! empty($this->getKpisFromMail($mail)['productsByCategory']);
        });
    }

    /** The report includes the top-selling products for the period. */
    public function test_report_includes_top_sold_products(): void
    {
        Mail::fake();

        $this->configureReport(recipients: ['test@ciclofinca.com']);

        $product = $this->createProduct($this->createSupplier());
        $sale = $this->createCompletedSale(Carbon::now()->subDays(2), total: 3000.0);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'name' => $product->name,
            'quantity' => 3,
            'unit_price' => 1000.0,
            'total' => 3000.0,
        ]);

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        Mail::assertSent(WeeklyDashboardReportMail::class, function ($mail) {
            return $this->getKpisFromMail($mail)['topProducts']->isNotEmpty();
        });
    }

    /** The --dry-run flag prints KPIs to the console without sending any emails. */
    public function test_dry_run_outputs_kpis_without_sending_emails(): void
    {
        Mail::fake();

        $this->configureReport(recipients: ['test@ciclofinca.com']);

        $this->artisan('reports:send-weekly-dashboard --dry-run')
            ->assertExitCode(0)
            ->expectsOutputToContain('DRY RUN');

        Mail::assertNothingSent();
    }

    /** Report KPIs match values computed directly from the database for the same period. */
    public function test_report_kpis_match_direct_query_for_same_period(): void
    {
        Mail::fake();

        $this->configureReport(recipients: ['test@ciclofinca.com']);

        $this->createCompletedSale(Carbon::now()->subDays(1), total: 12000.0);
        $this->createCompletedSale(Carbon::now()->subDays(2), total: 8000.0);

        $expectedTotal = Sale::whereBetween('sale_date', [
            Carbon::now()->startOfDay()->subDays(6),
            Carbon::now()->startOfDay(),
        ])->where('status', 'completed')->sum('total');

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        Mail::assertSent(WeeklyDashboardReportMail::class, function ($mail) use ($expectedTotal) {
            return (float) $this->getKpisFromMail($mail)['periodSales'] === (float) $expectedTotal;
        });
    }

    // -------------------------------------------------------------------------
    // Execution logging
    // -------------------------------------------------------------------------

    /** A successful run is logged with period, recipients, and sent count. */
    public function test_successful_execution_is_logged_with_required_context(): void
    {
        Mail::fake();
        /** @var MockInterface $logSpy */
        $logSpy = Log::spy();

        $recipients = ['log-test@ciclofinca.com'];
        $this->configureReport(recipients: $recipients);

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        $logSpy->shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($recipients) {
                return str_contains($message, 'reports:send-weekly-dashboard')
                    && isset($context['sent'], $context['period'], $context['executed_at'])
                    && $context['sent'] === 1
                    && $context['recipients'] === $recipients;
            });
    }

    /** A delivery failure is logged with the affected recipient and error detail. */
    public function test_failed_email_is_logged_with_recipient_and_error_detail(): void
    {
        /** @var MockInterface $logSpy */
        $logSpy = Log::spy();

        $this->configureReport(recipients: ['failure@ciclofinca.com']);

        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('SMTP connection refused'));

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(1);

        $logSpy->shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'reports:send-weekly-dashboard')
                    && isset($context['recipient'], $context['error']);
            });
    }

    /** The completion log always includes sent and failed counters. */
    public function test_execution_log_always_includes_sent_and_failed_counts(): void
    {
        Mail::fake();
        /** @var MockInterface $logSpy */
        $logSpy = Log::spy();

        $this->configureReport(recipients: ['a@ciclofinca.com', 'b@ciclofinca.com']);

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        $logSpy->shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'reports:send-weekly-dashboard')
                    && array_key_exists('sent', $context)
                    && array_key_exists('failed', $context);
            });
    }

    /** Missing recipients produce a warning instead of a normal execution log. */
    public function test_missing_recipients_logs_a_warning_instead_of_info(): void
    {
        /** @var MockInterface $logSpy */
        $logSpy = Log::spy();
        Mail::fake();

        AppSetting::setWeeklyReportRecipients([]);

        $this->artisan('reports:send-weekly-dashboard --force')
            ->assertExitCode(0);

        $logSpy->shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message) => str_contains($message, 'reports:send-weekly-dashboard'));

        Mail::assertNothingSent();
    }
}
