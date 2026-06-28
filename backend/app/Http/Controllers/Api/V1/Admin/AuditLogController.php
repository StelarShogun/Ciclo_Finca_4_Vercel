<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Audit\AuditLogIndexRequest;
use App\Services\Admin\Audit\AuditLogQuery;
use Illuminate\Http\JsonResponse;

/**
 * Bitácora de auditoría admin para el SPA Next (solo lectura). Reusa
 * AuditLogQuery: listado filtrable + opciones de acción/módulo.
 */
final class AuditLogController extends Controller
{
    public function index(AuditLogIndexRequest $request, AuditLogQuery $query): JsonResponse
    {
        return response()->json(['data' => $query->indexPayload($request->validated())]);
    }
}
