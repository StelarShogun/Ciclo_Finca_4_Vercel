<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Invoices\BuildInvoiceShowPage;
use App\Actions\Client\Invoices\BuildInvoicesIndexPage;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Facturas del cliente para el SPA Next. Reusa los builders (props extraídos
 * sin Inertia). El detalle valida pertenencia con InvoicePolicy: un cliente
 * nunca ve factura ajena. La impresión (Blade) se descarga desde la ruta web.
 */
final class InvoiceController extends Controller
{
    public function index(Request $request, BuildInvoicesIndexPage $page): JsonResponse
    {
        return response()->json(['data' => $page->props($request)]);
    }

    public function show(Sale $sale, BuildInvoiceShowPage $page): JsonResponse
    {
        $client = Auth::guard('clients')->user();
        if (! Gate::forUser($client)->allows('invoices.view', $sale)) {
            return response()->json(['message' => 'Factura no encontrada.'], 404);
        }

        return response()->json(['data' => $page->props($sale, $client)]);
    }
}
