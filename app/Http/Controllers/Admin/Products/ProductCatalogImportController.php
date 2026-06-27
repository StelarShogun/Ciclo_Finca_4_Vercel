<?php

namespace App\Http\Controllers\Admin\Products;

use App\Actions\Admin\Products\ExportProductCatalog;
use App\Actions\Admin\Products\ImportProductCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\ImportCatalogRequest;
use App\Services\Admin\ProductCatalog\CatalogImportProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductCatalogImportController extends Controller
{
    /**
     * Encola la importación y devuelve el importId para seguir el progreso.
     * El procesamiento corre en un job de cola para no bloquear al administrador.
     */
    public function import(ImportCatalogRequest $request, ImportProductCatalog $action): JsonResponse
    {
        return $action->handle($request, (int) Auth::guard('admin')->id());
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
