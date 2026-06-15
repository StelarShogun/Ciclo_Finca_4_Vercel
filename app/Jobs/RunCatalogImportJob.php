<?php

namespace App\Jobs;

use App\Exceptions\CatalogImportCancelled;
use App\Models\AdminUser;
use App\Services\Admin\ProductCatalog\CatalogImportProgress;
use App\Services\Admin\ProductCatalog\ProductCatalogImporter;
use App\Services\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Procesa una importación de catálogo en segundo plano, reportando avance en vivo
 * vía {@see CatalogImportProgress} para que el panel muestre una barra de progreso
 * reanudable sin bloquear al administrador.
 */
class RunCatalogImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public string $importId,
        public int $adminId,
        public string $storedPath,
        public string $originalName,
    ) {}

    public function handle(ProductCatalogImporter $importer, AuditLogger $audit): void
    {
        $absolutePath = Storage::disk('local')->path($this->storedPath);

        if (! is_file($absolutePath)) {
            CatalogImportProgress::put($this->importId, [
                'status' => 'failed',
                'level' => 'error',
                'message' => 'El archivo de importación ya no está disponible.',
            ]);

            return;
        }

        // El admin pudo cancelar mientras el job seguía en cola: abortamos antes
        // de tocar la base de datos.
        if (CatalogImportProgress::isCancelRequested($this->importId)) {
            CatalogImportProgress::put($this->importId, [
                'status' => 'cancelled',
                'level' => null,
                'message' => 'Importación cancelada antes de iniciar. No se aplicaron cambios.',
            ]);
            CatalogImportProgress::clearCancel($this->importId);
            Storage::disk('local')->delete($this->storedPath);

            return;
        }

        $file = new UploadedFile($absolutePath, $this->originalName, null, null, true);

        $lastWrite = 0.0;
        $importer->setProgressCallback(function (int $processed, int $total, array $stats) use (&$lastWrite): void {
            // Cancelación en vivo: lanzamos para romper la transacción del
            // importador y revertir lo procesado hasta ahora.
            if (CatalogImportProgress::isCancelRequested($this->importId)) {
                throw new CatalogImportCancelled();
            }

            $now = microtime(true);
            $isBoundary = $processed === 0 || $processed === $total;

            // Throttle a ~3 escrituras/seg para no saturar el cache en filas rápidas.
            if (! $isBoundary && ($now - $lastWrite) < 0.33) {
                return;
            }
            $lastWrite = $now;

            CatalogImportProgress::put($this->importId, [
                'status' => 'running',
                'total' => $total,
                'processed' => $processed,
                'created' => $stats['created'] ?? 0,
                'updated' => $stats['updated'] ?? 0,
                'skipped' => $stats['skipped'] ?? 0,
                'errors' => count($stats['errors'] ?? []),
                'message' => $total > 0
                    ? sprintf('Procesando %d de %d…', $processed, $total)
                    : 'Procesando…',
            ]);
        });

        try {
            CatalogImportProgress::put($this->importId, [
                'status' => 'running',
                'message' => 'Leyendo archivo…',
            ]);

            $stats = $importer->import($file);

            $importedCount = $stats['created'] + $stats['updated'];
            $level = match (true) {
                $importedCount === 0 => 'error',
                $stats['errors'] !== [] => 'warning',
                default => 'success',
            };

            $message = sprintf(
                'Importación finalizada: %d creados, %d actualizados, %d omitidos.',
                $stats['created'],
                $stats['updated'],
                $stats['skipped'],
            );

            if ($stats['errors'] !== []) {
                $message .= ' Errores: '.implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (y '.(count($stats['errors']) - 5).' más)';
                }
            }

            if (($stats['media_conversions_queued'] ?? 0) > 0) {
                $message .= sprintf(
                    ' Las miniaturas WebP de %d imagen(es) se generan en segundo plano.',
                    $stats['media_conversions_queued'],
                );
            }

            $admin = AdminUser::find($this->adminId);
            $audit->logAdminAction(
                'products_import',
                'products',
                'Products import processed ('.strtoupper(pathinfo($this->originalName, PATHINFO_EXTENSION)).').',
                [
                    'created' => $stats['created'],
                    'updated' => $stats['updated'],
                    'skipped' => $stats['skipped'],
                    'errors' => count($stats['errors']),
                ],
                $admin,
            );

            CatalogImportProgress::put($this->importId, [
                'status' => 'done',
                'level' => $level,
                'total' => $stats['rows_total'] ?? 0,
                'processed' => $stats['rows_total'] ?? 0,
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'errors' => count($stats['errors']),
                'message' => $message,
            ]);
        } catch (CatalogImportCancelled) {
            // La transacción ya revirtió todo lo procesado.
            CatalogImportProgress::put($this->importId, [
                'status' => 'cancelled',
                'level' => null,
                'total' => 0,
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'message' => 'Importación cancelada. No se aplicaron cambios.',
            ]);
        } catch (\Throwable $e) {
            Log::error('product_catalog_import_failed', ['error' => $e->getMessage()]);
            CatalogImportProgress::put($this->importId, [
                'status' => 'failed',
                'level' => 'error',
                'message' => 'No se pudo importar: '.$e->getMessage(),
            ]);
        } finally {
            CatalogImportProgress::clearCancel($this->importId);
            Storage::disk('local')->delete($this->storedPath);
        }
    }

    public function failed(\Throwable $exception): void
    {
        CatalogImportProgress::put($this->importId, [
            'status' => 'failed',
            'level' => 'error',
            'message' => 'La importación falló: '.$exception->getMessage(),
        ]);

        Storage::disk('local')->delete($this->storedPath);
    }
}
