<?php

namespace App\Http\Controllers\Admin\Products;

use App\Actions\Admin\Products\ExportProductCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\ImportCatalogRequest;
use App\Jobs\RunCatalogImportJob;
use App\Services\Admin\ProductCatalog\CatalogImportProgress;
use App\Services\Vercel\QstashPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCatalogImportController extends Controller
{
    /**
     * Encola la importación y devuelve el importId para seguir el progreso.
     * El procesamiento corre en un job de cola para no bloquear al administrador.
     */
    public function import(ImportCatalogRequest $request): JsonResponse
    {
        $adminId = (int) Auth::guard('admin')->id();
        $importId = (string) Str::uuid();
        $disk = config('vercel.enabled') ? (string) config('vercel.import_disk') : 'local';

        if ($request->filled('blob_path') && config('vercel.enabled')) {
            $blobPath = (string) $request->input('blob_path');
            $storedPath = (string) ($request->input('blob_url') ?: $blobPath);
            $originalName = (string) ($request->input('original_name') ?: basename($blobPath));

            if (! Storage::disk($disk)->exists($storedPath)) {
                return response()->json([
                    'message' => 'El archivo subido a Blob no está disponible para importar.',
                ], 422);
            }
        } else {
            /** @var UploadedFile $file */
            $file = $request->file('import_file');
            $extension = strtolower($file->getClientOriginalExtension()) ?: 'dat';
            $storedPath = $file->storeAs((string) config('vercel.import_prefix', 'catalog-imports'), $importId.'.'.$extension, $disk);
            $originalName = $file->getClientOriginalName();
        }

        $progress = CatalogImportProgress::queued($importId, $adminId, $originalName);

        if (config('vercel.enabled')) {
            app(QstashPublisher::class)->publish(
                'internal/vercel/jobs/catalog-import?key='.(string) config('app.deploy_secret'),
                [
                    'importId' => $importId,
                    'adminId' => $adminId,
                    'storedPath' => $storedPath,
                    'originalName' => $originalName,
                    'disk' => $disk,
                ],
            );
        } else {
            RunCatalogImportJob::dispatch($importId, $adminId, $storedPath, $originalName, $disk);
        }

        return response()->json([
            'importId' => $importId,
            'progress' => $progress,
        ], 202);
    }

    /**
     * Devuelve el estado de progreso de una importación concreta (polling).
     */
    public function importProgress(string $importId): JsonResponse
    {
        $progress = CatalogImportProgress::get($importId);

        if ($progress === null) {
            return response()->json(['status' => 'unknown'], 404);
        }

        return response()->json($progress);
    }

    /**
     * Importación activa del administrador actual, para reanudar la vista al reabrir.
     */
    public function importActive(): JsonResponse
    {
        $adminId = (int) Auth::guard('admin')->id();
        $importId = CatalogImportProgress::activeFor($adminId);
        $imports = CatalogImportProgress::importsFor($adminId);

        if ($importId === null) {
            return response()->json(['importId' => null, 'progress' => null, 'imports' => $imports]);
        }

        return response()->json([
            'importId' => $importId,
            'progress' => CatalogImportProgress::get($importId),
            'imports' => $imports,
        ]);
    }

    /**
     * Solicita la cancelación de una importación en curso o en cola.
     * El job la detecta y aborta; la transacción revierte lo procesado.
     */
    public function importCancel(string $importId): JsonResponse
    {
        $adminId = (int) Auth::guard('admin')->id();
        $progress = CatalogImportProgress::get($importId);

        if ($progress === null || ! CatalogImportProgress::ownsImport($adminId, $importId)) {
            return response()->json(['status' => 'unknown'], 404);
        }

        $status = $progress['status'] ?? null;

        if (in_array($status, CatalogImportProgress::TERMINAL_STATUSES, true)) {
            return response()->json($progress);
        }

        CatalogImportProgress::requestCancel($importId);

        if ($status === 'queued') {
            // El job aún no arrancó: lo damos por cancelado de inmediato.
            $progress = CatalogImportProgress::put($importId, [
                'status' => 'cancelled',
                'level' => null,
                'message' => 'Importación cancelada antes de iniciar. No se aplicaron cambios.',
            ]);
        } else {
            $progress = CatalogImportProgress::put($importId, [
                'status' => 'cancelling',
                'level' => null,
                'message' => 'Cancelando importación…',
            ]);
        }

        return response()->json($progress);
    }

    /**
     * Olvida la importación activa (al cerrar el resumen o empezar otra).
     */
    public function importDismiss(Request $request): JsonResponse
    {
        $importId = $request->input('importId');

        CatalogImportProgress::dismissFor(
            (int) Auth::guard('admin')->id(),
            is_string($importId) ? $importId : null,
        );

        return response()->json(['ok' => true]);
    }

    public function export(Request $request, ExportProductCatalog $action, ?string $format = null)
    {
        return $action->handle($request, $format);
    }
}
