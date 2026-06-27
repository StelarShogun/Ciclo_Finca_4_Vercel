<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Services\Admin\Sales\OrderCancellationNotifier;
use App\Services\Shared\Security\SensitiveDataMasker;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteExpiredSalesCommand extends Command
{
    protected $signature = 'sales:delete-expired';

    protected $description = 'Cancela pedidos pendientes que superaron el tiempo de vigencia configurado (order_expiry_days).';

    public function handle(OrderCancellationNotifier $notifier)
    {
        $days = Sale::getOrderExpirationDays();
        $limitDate = Carbon::now()->subDays($days);
        $query = Sale::where('sale_date', '<', $limitDate)
            ->where('status', 'pending');
        $count = $query->count();
        if ($count === 0) {
            $this->info('No hay pedidos pendientes expirados para cancelar.');

            return self::SUCCESS;
        }
        $this->info("Cancelando {$count} pedido(s) pendientes con fecha anterior a ".$limitDate->format('Y-m-d H:i:s').'.');

        $cancelledAt = Carbon::now();
        $reason = 'Cancelado automáticamente por vencimiento del plazo';

        $query->get()->each(function (Sale $sale) use ($cancelledAt, $reason, $notifier) {
            $existingNotes = trim((string) ($sale->notes ?? ''));
            $autoNote = sprintf(
                '[%s] %s.',
                $cancelledAt->format('Y-m-d H:i:s'),
                $reason
            );

            $sale->update([
                'status' => 'cancelled',
                'notes' => $existingNotes !== '' ? $existingNotes.PHP_EOL.$autoNote : $autoNote,
            ]);

            try {
                $notifier->notify($sale, $reason, $cancelledAt);
            } catch (\Throwable $e) {
                Log::warning('Automatic cancellation notification failed.', SensitiveDataMasker::exceptionContext($e, [
                    'sale_id' => $sale->sale_id,
                ]));
            }
        });

        $this->info('Pedidos expirados cancelados correctamente.');

        return self::SUCCESS;
    }
}
