<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Services\AuditLogger;
use App\Services\InventoryMovementService;
use App\Services\OrderCancellationNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelExpiredReadyOrdersCommand extends Command
{
    protected $signature = 'orders:cancel-expired-ready
                            {--dry-run : Lista los pedidos afectados sin realizar cambios}
                            {--minutes= : (Solo pruebas) Override del plazo en minutos desde ready_at}';

    protected $description = 'Cancela pedidos en estado "listo para recoger" que superaron el plazo configurado sin ser confirmados, devolviendo el stock al inventario.';

    public function handle(
        InventoryMovementService $inventoryService,
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

            $cutoff = Carbon::now()->subMinutes($minutes);
            $windowLabel = $minutes.' min';
        } else {
            $days = Sale::getReadyToPickupExpirationDays();
            $cutoff = Carbon::now()->subDays($days);
            $windowLabel = $days.' día(s)';
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
            $inventoryService,
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
                DB::transaction(function () use ($sale, $reason, $inventoryService): void {
                    $cancellationNote = 'Cancelado automáticamente por vencimiento del plazo de recogida.';
                    $existingNotes = $sale->notes ? $sale->notes."\n" : '';
                    $sale->update(['status' => 'cancelled', 'notes' => $existingNotes.$cancellationNote]);

                    foreach ($sale->saleItems as $item) {
                        if ($item->product) {
                            $inventoryService->recordOrderCancellation(
                                product: $item->product,
                                quantity: (int) $item->quantity,
                                saleId: $sale->sale_id,
                                reason: $reason,
                            );
                        }
                    }
                });

                try {
                    $notifier->notify($sale, $reason, $cancelledAt);
                } catch (\Throwable $e) {
                    Log::warning('Auto-cancellation notification failed.', [
                        'sale_id' => $sale->sale_id,
                        'error' => $e->getMessage(),
                    ]);
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
                    Log::warning('Auto-cancellation audit log failed.', [
                        'sale_id' => $sale->sale_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $this->info("Pedido {$label} cancelado y stock devuelto correctamente.");
                $cancelled++;

            } catch (\Throwable $e) {
                $failed++;
                $this->error("Error al cancelar el pedido {$label}: {$e->getMessage()}");
                Log::error('Auto-cancellation of expired ready_to_pickup order failed.', [
                    'sale_id' => $sale->sale_id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        if (! $isDryRun) {
            $this->info("Finalizado: {$cancelled} pedido(s) cancelado(s), {$failed} fallido(s).");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
