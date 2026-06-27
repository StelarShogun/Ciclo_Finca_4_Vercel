<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clients\ClientPurchaseHistoryIndexRequest;
use App\Http\Requests\Admin\Clients\ClientPurchaseHistoryTableRequest;
use App\Services\Admin\ClientPurchaseHistoryQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * CF4-33 — historial de compras por cliente (ventas completadas con cliente).
 */
final class ClientPurchaseHistoryController extends Controller
{
    public function index(ClientPurchaseHistoryIndexRequest $request, ClientPurchaseHistoryQuery $query)
    {
        return Inertia::render('Admin/Reports/ClientPurchases', $query->indexPayload($request->validated()));
    }

    public function show(Request $request, ClientPurchaseHistoryQuery $query, int $client)
    {
        return Inertia::render(
            'Admin/Reports/ClientPurchasesShow',
            $query->showPayload($client, $request->query()),
        );
    }

    public function table(ClientPurchaseHistoryTableRequest $request, ClientPurchaseHistoryQuery $query)
    {
        return response()->json($query->tablePayload($request->validated()));
    }

    public function clientOrders(Request $request, ClientPurchaseHistoryQuery $query, int $client)
    {
        $payload = $query->clientOrdersPayload($client, (string) $request->query('period', '30d'));
        $status = (int) ($payload['status'] ?? 200);
        unset($payload['status']);

        return response()->json($payload, $status);
    }
}
