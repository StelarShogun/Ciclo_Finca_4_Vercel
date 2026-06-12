<?php

use App\Models\AppSetting;
use App\Support\SchedulerMonitor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('scheduler:heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

// Cancela pedidos pendientes que superaron el plazo de vigencia configurado.
SchedulerMonitor::track(
    Schedule::command('sales:delete-expired')->dailyAt('00:00'),
    'sales:delete-expired',
    'sales_delete_expired'
)->withoutOverlapping();

// Envía recordatorio a clientes con pedidos que vencen en menos de 24 horas.
SchedulerMonitor::track(
    Schedule::command('sales:send-expiry-reminders')->dailyAt('09:00'),
    'sales:send-expiry-reminders',
    'sales_send_expiry_reminders'
)->withoutOverlapping();

// Cancela pedidos en "listo para recoger" que superaron 3 días sin confirmarse
// y devuelve el stock al inventario.
// Previsualizar sin cambios: php artisan orders:cancel-expired-ready --dry-run
SchedulerMonitor::track(
    Schedule::command('orders:cancel-expired-ready')->dailyAt('01:00'),
    'orders:cancel-expired-ready',
    'orders_cancel_expired_ready'
)->withoutOverlapping();

// ── Reporte semanal de KPIs del dashboard ─────────────────────────────
// El día, hora y minuto se leen de AppSetting en cada ciclo del scheduler,
// por lo que cualquier cambio del administrador se aplica desde la siguiente
// ejecución sin redeployar ni reiniciar ningún proceso.
//
// Previsualizar sin enviar: php artisan reports:send-weekly-dashboard --dry-run
// Forzar envío inmediato:   php artisan reports:send-weekly-dashboard --force
$reportDay = AppSetting::getWeeklyReportDay();    // 0 = Dom … 6 = Sáb
$reportHour = AppSetting::getWeeklyReportHour();   // 0–23
$reportMinute = AppSetting::getWeeklyReportMinute(); // 0–59

SchedulerMonitor::track(
    Schedule::command('reports:send-weekly-dashboard')
        ->cron(sprintf('%d %d * * %d', $reportMinute, $reportHour, $reportDay)),
    'reports:send-weekly-dashboard',
    'reports_send_weekly_dashboard'
)->withoutOverlapping();

SchedulerMonitor::track(
    Schedule::command('cf4:cleanup-temp-product-images')->dailyAt('03:30'),
    'cf4:cleanup-temp-product-images',
    'cf4_cleanup_temp_product_images'
)->withoutOverlapping();

// Product images imported in bulk may miss WebP conversions if the post-import
// worker is interrupted — this catches them without admin action.
SchedulerMonitor::track(
    Schedule::command('cf4:regenerate-missing-media-conversions')->everyFifteenMinutes(),
    'cf4:regenerate-missing-media-conversions',
    'cf4_regenerate_missing_media_conversions'
)->withoutOverlapping();

Schedule::command('pulse:check')->everyFiveMinutes();
