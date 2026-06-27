<?php

namespace App\Actions\Admin\Products;

use App\Http\Requests\Admin\Products\ImportCatalogRequest;
use App\Jobs\RunCatalogImportJob;
use App\Services\Admin\ProductCatalog\CatalogImportProgress;
use App\Services\Admin\ProductCatalog\ProductCatalogImportStorage;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Services\Vercel\QstashPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class ImportProductCatalog
{
    public function __construct(private ProductCatalogImportStorage $storage) {}

    public function handle(ImportCatalogRequest $request, int $adminId): JsonResponse
    {
        $stored = $this->storage->store($request);

        if (isset($stored['error'])) {
            return response()->json(['message' => $stored['error']], 422);
        }

        $importId = $stored['importId'];
        $disk = $stored['disk'];
        $storedPath = $stored['storedPath'];
        $originalName = $stored['originalName'];
        $progress = CatalogImportProgress::queued($importId, $adminId, $originalName);

        try {
            $this->dispatch($importId, $adminId, $storedPath, $originalName, $disk);
        } catch (\Throwable $e) {
            Log::error('catalog_import_orchestration_failed', SensitiveDataMasker::exceptionContext($e, [
                'importId' => $importId,
                'storedPath' => $storedPath,
                'disk' => $disk,
            ]));

            $message = 'No se pudo iniciar la importación. Inténtalo de nuevo o revisa los logs.';
            CatalogImportProgress::put($importId, [
                'status' => 'failed',
                'level' => 'error',
                'message' => $message,
            ]);

            return response()->json([
                'importId' => $importId,
                'message' => $message,
            ], 502);
        }

        return response()->json([
            'importId' => $importId,
            'progress' => $progress,
        ], 202);
    }

    private function dispatch(string $importId, int $adminId, string $storedPath, string $originalName, string $disk): void
    {
        if (! config('vercel.enabled')) {
            RunCatalogImportJob::dispatch($importId, $adminId, $storedPath, $originalName, $disk);

            return;
        }

        $payload = [
            'importId' => $importId,
            'adminId' => $adminId,
            'storedPath' => $storedPath,
            'originalName' => $originalName,
            'disk' => $disk,
        ];

        if ((string) config('vercel.qstash_token', '') !== '') {
            app(QstashPublisher::class)->publish(
                'internal/vercel/jobs/catalog-import',
                $payload,
                forwardHeaders: ['X-Deploy-Secret' => (string) config('app.deploy_secret')],
            );

            return;
        }

        app()->call([
            new RunCatalogImportJob($importId, $adminId, $storedPath, $originalName, $disk),
            'handle',
        ]);
    }
}
