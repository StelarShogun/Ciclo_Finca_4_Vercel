<?php

use App\Models\AppSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cancela pedidos pendientes que superaron el plazo de vigencia configurado.
Schedule::command('sales:delete-expired')
    ->dailyAt('00:00')
    ->withoutOverlapping();

// Envía recordatorio a clientes con pedidos que vencen en menos de 24 horas.
Schedule::command('sales:send-expiry-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Cancela pedidos en "listo para recoger" que superaron 3 días sin confirmarse
// y devuelve el stock al inventario.
// Previsualizar sin cambios: php artisan orders:cancel-expired-ready --dry-run
Schedule::command('orders:cancel-expired-ready')
    ->dailyAt('01:00')
    ->withoutOverlapping();

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

Schedule::command('reports:send-weekly-dashboard')
    ->cron(sprintf('%d %d * * %d', $reportMinute, $reportHour, $reportDay))
    ->withoutOverlapping();

Schedule::command('cf4:cleanup-temp-product-images')
    ->dailyAt('03:30')
    ->withoutOverlapping();
