<?php

namespace App\Actions\Admin\Products;

use App\Http\Requests\ImportCatalogRequest;
use App\Services\Admin\Products\ProductAuditLogger;
use App\Support\ProductCatalog\ProductCatalogImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

final class ImportProductCatalog
{
    public function __construct(
        private ProductCatalogImporter $importer,
        private ProductAuditLogger $audit,
    ) {}

    public function handle(ImportCatalogRequest $request): JsonResponse|RedirectResponse
    {
        try {
            /** @var UploadedFile $file */
            $file = $request->file('import_file');
            $stats = $this->importer->import($file);

            $this->audit->log('products_import', 'Products import processed ('.strtoupper($file->getClientOriginalExtension()).').', [
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'errors' => count($stats['errors']),
            ]);

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
                    ' Las miniaturas WebP de %d imagen(es) se generan en segundo plano; si algo queda pendiente, el sistema lo reintenta solo.',
                    $stats['media_conversions_queued'],
                );
            }

            $importedCount = $stats['created'] + $stats['updated'];
            $level = match (true) {
                $importedCount === 0 => 'error',
                $stats['errors'] !== [] => 'warning',
                default => 'success',
            };

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'level' => $level,
                    'stats' => $stats,
                ]);
            }

            if ($level === 'error') {
                return redirect()->route('inventory')->with('error', $message);
            }

            if ($level === 'warning') {
                return redirect()->route('inventory')->with('warning', $message);
            }

            return redirect()->route('inventory')->with('status', $message);
        } catch (\Throwable $e) {
            Log::error('product_catalog_import_failed', ['error' => $e->getMessage()]);

            $message = 'No se pudo importar: '.$e->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'level' => 'error'], 500);
            }

            return redirect()->route('inventory')->with('error', $message);
        }
    }
}
