<?php

namespace App\Console;

use App\Console\Commands\CancelExpiredReadyOrdersCommand;
use App\Console\Commands\DeleteExpiredSalesCommand;
use App\Console\Commands\SendOrderExpiryRemindersCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        DeleteExpiredSalesCommand::class,
        SendOrderExpiryRemindersCommand::class,
        CancelExpiredReadyOrdersCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Cancela pedidos pendientes que superaron el plazo de vigencia configurado.
        $schedule->command('sales:delete-expired')
            ->dailyAt('00:00')
            ->withoutOverlapping();

        // Envía recordatorio a clientes con pedidos que vencen en menos de 24 horas.
        $schedule->command('sales:send-expiry-reminders')
            ->dailyAt('09:00')
            ->withoutOverlapping();

        // Cancela pedidos en "listo para recoger" que superaron 3 días sin confirmarse
        // y devuelve el stock al inventario.
        // Previsualizar sin cambios: php artisan orders:cancel-expired-ready --dry-run
        $schedule->command('orders:cancel-expired-ready')
            ->dailyAt('01:00')
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
