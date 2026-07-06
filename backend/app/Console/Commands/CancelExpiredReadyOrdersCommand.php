<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Services\Admin\Audit\AuditLogger;
use App\Services\Admin\Sales\AdminSalesWorkflowService;
use App\Services\Admin\Sales\OrderCancellationNotifier;
use App\Services\Shared\Sales\OrderExpirationPolicy;
use App\Services\Shared\Security\SensitiveDataMasker;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelExpiredReadyOrdersCommand extends Command
{
    protected $signature = 'orders:cancel-expired-ready
                            {--dry-run : Lista los pedidos afectados sin realizar cambios}
                            {--minutes= : (Solo pruebas) Override del plazo en minutos desde ready_at}';

    protected $description = 'Cancela pedidos en estado "listo para recoger" que superaron el plazo configurado sin ser confirmados, devolviendo el stock al inventario.';

    public function handle(
        AdminSalesWorkflowService $workflow,
        OrderExpirationPolicy $expirationPolicy,
        OrderCancellationNotifier $notifier,
        AuditLogger $auditLogger,
    ): int {
        $isDryRun = $this->option('dry-run');
        $minutesOpt = $this->option('minutes');

        if ($minutesOpt !== null) {
            $minutes = (int) $minutesOpt;
            if ($minutes <= 0) {
                $this->error('La opción --minutes debe ser un entero mayor que cero.');

                return self::FAILURE;
            }

            $cutoff = $expirationPolicy->readyToPickupCutoff($minutes);
            $windowLabel = $minutes.' min';
        } else {
            $hours = $expirationPolicy->readyToPickupExpirationHours();
            $cutoff = $expirationPolicy->readyToPickupCutoff();
            $windowLabel = $hours.' hora(s)';
        }
        $reason = 'Por vencimiento de encargo';
        $cancelledAt = Carbon::now();

        $query = Sale::with('saleItems.product')
            ->where('status', 'ready_to_pickup')
            ->whereNotNull('ready_at')
            ->where('ready_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No hay pedidos en "listo para recoger" expirados para cancelar.');

            return self::SUCCESS;
        }

        $this->info(
            "Encontrados {$count} pedido(s) en 'listo para recoger' con ready_at anterior a "
            .$cutoff->format('Y-m-d H:i:s')
            .' ('.$windowLabel.')'
            .($isDryRun ? ' [DRY RUN — sin cambios]' : '.')
        );

        $cancelled = 0;
        $failed = 0;

        $query->get()->each(function (Sale $sale) use (
            $isDryRun,
            $reason,
            $cancelledAt,
            $workflow,
            $notifier,
            $auditLogger,
            &$cancelled,
            &$failed,
        ): void {
            $label = $sale->invoice_number ?? ('#'.$sale->sale_id);

            if ($isDryRun) {
                $this->line("  [dry-run] Se cancelaría el pedido {$label} (ready_at: {$sale->ready_at})");

                return;
            }

            try {
                $workflow->cancelExpiredReadyOrder(
                    $sale,
                    $reason,
                    'Cancelado automáticamente por vencimiento del plazo de recogida.',
                );

                try {
                    $notifier->notify($sale, $reason, $cancelledAt);
                } catch (\Throwable $e) {
                    Log::warning('Auto-cancellation notification failed.', SensitiveDataMasker::exceptionContext($e, [
                        'sale_id' => $sale->sale_id,
                    ]));
                }

                try {
                    $auditLogger->logAdminAction(
                        'sale_auto_cancel',
                        'sales',
                        'Pedido cancelado automáticamente por vencimiento de plazo de recogida.',
                        [
                            'sale_id' => (int) $sale->sale_id,
                            'invoice_number' => (string) ($sale->invoice_number ?? ''),
                            'ready_at' => (string) $sale->ready_at,
                            'cancelled_at' => $cancelledAt->toISOString(),
                            'from_status' => 'ready_to_pickup',
                            'to_status' => 'cancelled',
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning('Auto-cancellation audit log failed.', SensitiveDataMasker::exceptionContext($e, [
                        'sale_id' => $sale->sale_id,
                    ]));
                }

                $this->info("Pedido {$label} cancelado y stock devuelto correctamente.");
                $cancelled++;

            } catch (\Throwable $e) {
                $failed++;
                $this->error("Error al cancelar el pedido {$label}. Revisa los logs.");
                Log::error('Auto-cancellation of expired ready_to_pickup order failed.', SensitiveDataMasker::exceptionContext($e, [
                    'sale_id' => $sale->sale_id,
                ]));
            }
        });

        if (! $isDryRun) {
            $this->info("Finalizado: {$cancelled} pedido(s) cancelado(s), {$failed} fallido(s).");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
