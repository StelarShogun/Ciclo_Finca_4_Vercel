<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPurchaseHistoryTableRequest;
use App\Models\Client;
use App\Services\Admin\ClientPurchaseHistoryQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * CF4-33 — historial de compras por cliente (ventas completadas con cliente).
 */
class ClientPurchaseHistoryController extends Controller
{
    public function index(Request $request)
    {
        $period = (string) $request->query('period', '30d');
        if (! in_array($period, ClientPurchaseHistoryQuery::PERIODS, true)) {
            $period = '30d';
        }
        $sort = (string) $request->query('sort', 'total_purchased');
        if (! in_array($sort, ClientPurchaseHistoryQuery::SORTS, true)) {
            $sort = 'total_purchased';
        }
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';

        return view('admin.reports.client-purchases', [
            'period' => $period,
            'sort' => $sort,
            'dir' => $dir,
            'q' => mb_substr($q, 0, 100),
        ]);
    }

    public function table(ClientPurchaseHistoryTableRequest $request)
    {
        $validated = $request->validated();
        $period = $validated['period'];
        $q = isset($validated['q']) ? trim((string) $validated['q']) : '';
        $sort = $validated['sort'];
        $dir = $validated['dir'];
        $page = max(1, (int) ($validated['page'] ?? 1));

        [$start, $end] = ClientPurchaseHistoryQuery::periodBounds($period);

        $aggregateQuery = ClientPurchaseHistoryQuery::baseAggregates($start, $end, $q);
        $total = (int) DB::query()->fromSub($aggregateQuery, 'client_purchase_agg')->count();

        $sorted = ClientPurchaseHistoryQuery::baseAggregates($start, $end, $q);
        ClientPurchaseHistoryQuery::applySort($sorted, $sort, $dir);

        $perPage = 15;
        $results = $sorted
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $rows = $results->map(fn ($row) => ClientPurchaseHistoryQuery::formatAggregateRow($row))->values();

        $paginator = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => route('admin.reports.client-purchases.table'),
                'pageName' => 'page',
            ]
        );
        $paginator->appends($request->query());

        return response()->json([
            'success' => true,
            'period' => $period,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q,
            'rows' => $rows,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'pagination_html' => view('components.pagination', [
                'paginator' => $paginator,
                'label' => 'clientes',
            ])->render(),
        ]);
    }

    /**
     * Órdenes (ventas) del cliente en el mismo periodo que el listado.
     */
    public function clientOrders(Request $request, int $client)
    {
        $period = (string) $request->query('period', '30d');
        if (! in_array($period, ClientPurchaseHistoryQuery::PERIODS, true)) {
            $period = '30d';
        }

        [$start, $end] = ClientPurchaseHistoryQuery::periodBounds($period);

        $clientRow = Client::query()->where('user_id', $client)->first();
        if (! $clientRow) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
        }

        $hasSalesInPeriod = DB::table('sales')
            ->where('client_id', $client)
            ->where('status', 'completed')
            ->where('sale_date', '>=', $start)
            ->where('sale_date', '<=', $end)
            ->exists();

        if (! $hasSalesInPeriod) {
            return response()->json(['success' => false, 'message' => 'Sin compras en el periodo.'], 404);
        }

        $orders = DB::table('sales')
            ->where('client_id', $client)
            ->where('status', 'completed')
            ->where('sale_date', '>=', $start)
            ->where('sale_date', '<=', $end)
            ->orderByDesc('sale_date')
            ->get(['sale_id', 'invoice_number', 'sale_date', 'total', 'status']);

        $parts = array_filter([
            (string) $clientRow->name,
            (string) ($clientRow->first_surname ?? ''),
            (string) ($clientRow->second_surname ?? ''),
        ], fn (string $p): bool => $p !== '');
        $displayName = trim(implode(' ', $parts));

        return response()->json([
            'success' => true,
            'client' => [
                'client_id' => (int) $clientRow->user_id,
                'display_name' => $displayName !== '' ? $displayName : $clientRow->gmail,
                'gmail' => (string) $clientRow->gmail,
            ],
            'orders' => $orders->map(function ($row) {
                $dt = Carbon::parse($row->sale_date, config('app.timezone'));

                return [
                    'sale_id' => (int) $row->sale_id,
                    'invoice_number' => (string) $row->invoice_number,
                    'sale_date' => $dt->format('d/m/Y H:i'),
                    'total' => round((float) $row->total, 2),
                    'status' => (string) $row->status,
                ];
            })->values(),
        ]);
    }
}
