<?php

namespace App\Console\Commands;

use App\Mail\WeeklyDashboardReportMail;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Services\Shared\Security\SensitiveDataMasker;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDashboardReportCommand extends Command
{
    protected $signature = 'reports:send-weekly-dashboard
                            {--dry-run : Muestra los KPIs y destinatarios sin enviar correos}
                            {--force  : Envía aunque no sea el día/hora configurados (útil para pruebas)}';

    protected $description = 'Envía el reporte semanal de KPIs del dashboard a los destinatarios configurados.';

    // ─── Entry point ─────────────────────────────────────────────────────────

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForced = (bool) $this->option('force');

        $recipients = AppSetting::getWeeklyReportRecipients();

        if (empty($recipients)) {
            $this->warn('No hay destinatarios configurados. Configure al menos un correo en Ajustes → Reporte semanal.');
            Log::warning('reports:send-weekly-dashboard — abortado: sin destinatarios configurados.');

            return self::SUCCESS;
        }

        if (! $isForced && ! $isDryRun) {
            // El scheduler ya restringe la ejecución al día+hora+minuto correctos
            // mediante el cron dinámico del Kernel; esta guarda es una red de
            // seguridad para ejecuciones manuales fuera del momento configurado.
            $configuredDay = AppSetting::getWeeklyReportDay();    // 0 = Dom … 6 = Sáb
            $configuredHour = AppSetting::getWeeklyReportHour();   // 0–23
            $configuredMinute = AppSetting::getWeeklyReportMinute(); // 0–59
            $now = Carbon::now();

            if (
                (int) $now->format('w') !== $configuredDay ||
                (int) $now->format('G') !== $configuredHour ||
                (int) $now->format('i') !== $configuredMinute
            ) {
                $this->info('No es el momento configurado para el envío. Use --force para forzar el envío ahora mismo.');

                return self::SUCCESS;
            }
        }

        $periodEnd = Carbon::now()->startOfDay();
        $periodStart = $periodEnd->copy()->subDays(6); // últimos 7 días, igual que el dashboard

        $kpis = $this->buildKpis($periodStart, $periodEnd);

        if ($isDryRun) {
            $this->renderDryRun($kpis, $recipients, $periodStart, $periodEnd);

            return self::SUCCESS;
        }

        return $this->sendEmails($kpis, $recipients, $periodStart, $periodEnd);
    }

    // ─── KPI builder (reuses the same queries as DashboardController) ─────────

    /**
     * @return array<string, mixed>
     */
    private function buildKpis(Carbon $periodStart, Carbon $periodEnd): array
    {
        // ── Totals ────────────────────────────────────────────────────────────
        $totalProducts = Product::count();
        $totalSuppliers = Supplier::count();
        $totalCategories = Category::count();

        // ── Sales in period ───────────────────────────────────────────────────
        $periodSales = Sale::whereBetween('sale_date', [$periodStart, $periodEnd->copy()->endOfDay()])
            ->where('status', 'completed')
            ->sum('total');

        $periodSalesCount = Sale::whereBetween('sale_date', [$periodStart, $periodEnd->copy()->endOfDay()])
            ->where('status', 'completed')
            ->count();

        // ── Sales by day (chart series, same logic as DashboardController::fillSalesChartSeries) ──
        $salesRows = Sale::query()
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('SUM(total) as total')
            )
            ->where('sale_date', '>=', $periodStart)
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->orderBy('date')
            ->get();

        $salesByDay = $this->fillSalesChartSeries($salesRows, $periodStart, $periodEnd);

        // ── Low stock ────────────────────────────────────────────────────────
        $lowStockCount = Product::lowStockAlert()->count();

        $lowStockList = Product::with(['category', 'supplier'])
            ->lowStockAlert()
            ->orderBy('stock_current', 'asc')
            ->limit(5)
            ->get();

        // ── Products by category (same query as DashboardController) ─────────
        $productsByCategory = Category::withCount(['products' => function ($query) {
            $query->where('status', 'active');
        }])
            ->orderBy('products_count', 'desc')
            ->get()
            ->map(fn (Category $c) => [
                'categoria' => $c->name,
                'total' => $c->products_count,
            ]);

        // ── Top products in period ────────────────────────────────────────────
        $topProducts = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$periodStart, $periodEnd->copy()->endOfDay()])
            ->select(
                'products.name',
                'products.image',
                DB::raw('SUM(sale_items.quantity) as total_vendido'),
                DB::raw('SUM(sale_items.total) as ingresos')
            )
            ->groupBy('products.product_id', 'products.name', 'products.image')
            ->orderBy('total_vendido', 'desc')
            ->limit(5)
            ->get();

        return compact(
            'totalProducts',
            'totalSuppliers',
            'totalCategories',
            'periodSales',
            'periodSalesCount',
            'salesByDay',
            'lowStockCount',
            'lowStockList',
            'productsByCategory',
            'topProducts'
        );
    }

    // ─── Sending ──────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $kpis
     * @param  string[]  $recipients
     */
    private function sendEmails(array $kpis, array $recipients, Carbon $periodStart, Carbon $periodEnd): int
    {
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new WeeklyDashboardReportMail($kpis, $periodStart, $periodEnd));
                $this->info("Reporte enviado → {$email}");
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Error al enviar a {$email}. Revisa los logs.");
                Log::error('reports:send-weekly-dashboard — fallo al enviar correo.', SensitiveDataMasker::exceptionContext($e, [
                    'recipient_hash' => hash('sha256', $email),
                ]));
            }
        }

        $start = $periodStart->format('Y-m-d');
        $end = $periodEnd->format('Y-m-d');

        Log::info('reports:send-weekly-dashboard — ejecución finalizada.', [
            'period' => "{$start} → {$end}",
            'recipients' => $recipients,
            'sent' => $sent,
            'failed' => $failed,
            'executed_at' => Carbon::now()->toISOString(),
        ]);

        $this->info("Finalizado: {$sent} correo(s) enviado(s), {$failed} fallido(s).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ─── Dry-run output ───────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $kpis
     * @param  string[]  $recipients
     */
    private function renderDryRun(array $kpis, array $recipients, Carbon $periodStart, Carbon $periodEnd): void
    {
        $this->info('[DRY RUN] No se enviarán correos.');
        $this->line('');
        $this->line("Período   : {$periodStart->format('d/m/Y')} → {$periodEnd->format('d/m/Y')}");
        $this->line('Ventas    : ₡'.number_format($kpis['periodSales'], 0, ',', '.')." ({$kpis['periodSalesCount']} pedidos)");
        $this->line("Stock bajo: {$kpis['lowStockCount']} producto(s)");
        $this->line("Productos : {$kpis['totalProducts']}");
        $this->line('');
        $this->line('Destinatarios:');

        foreach ($recipients as $email) {
            $this->line("  - {$email}");
        }
    }

    // ─── Helpers (mirrors DashboardController::fillSalesChartSeries) ─────────

    /**
     * @param  iterable<int, object>  $rows
     * @return array<int, array{date: string, total: float}>
     */
    private function fillSalesChartSeries(iterable $rows, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $byDate = [];
        foreach ($rows as $row) {
            $d = data_get($row, 'date');
            $key = $d instanceof Carbon ? $d->format('Y-m-d') : substr((string) $d, 0, 10);
            $byDate[$key] = (float) data_get($row, 'total');
        }

        $out = [];
        $cursor = $rangeStart->copy()->startOfDay();
        $end = $rangeEnd->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $out[] = ['date' => $key, 'total' => $byDate[$key] ?? 0.0];
            $cursor->addDay();
        }

        return $out;
    }
}
