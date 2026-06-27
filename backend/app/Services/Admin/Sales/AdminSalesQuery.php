<?php

namespace App\Services\Admin\Sales;

use App\Models\Sale;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
use App\Support\DashboardTodaySales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class AdminSalesQuery
{
    public function indexPayload(Request $request): array
    {
        $statusFilter = $request->query('status');
        $salesStatusUi = in_array($statusFilter, ['cancelled', 'returned', 'all'], true)
            ? $statusFilter
            : 'completed';

        $query = Sale::with(['client', 'sellerAdmin', 'saleItems.product']);
        $this->applyListFilters($query, $request);

        $sales = $query
            ->orderByDesc('sale_date')
            ->paginate(AdminPerPage::resolve($request->input('per_page', 10)))
            ->withQueryString();

        return [
            'sales' => $this->rows(collect($sales->items())),
            'pagination' => ListPaginationPayload::from($sales),
            'kpis' => $this->kpis(),
            'salesStatusUi' => $salesStatusUi,
            'latestHistorySaleId' => (int) ((clone $this->historyBaseQuery())->max('sale_id') ?? 0),
            'filters' => [
                'status' => $salesStatusUi,
                'date_range' => (string) $request->query('date_range', 'today'),
                'payment_method' => (string) $request->query('payment_method', ''),
                'search' => (string) $request->query('search', ''),
                'date_from' => (string) $request->query('date_from', ''),
                'date_to' => (string) $request->query('date_to', ''),
            ],
        ];
    }

    public function heartbeatPayload(int $since): array
    {
        $baseQuery = $this->historyBaseQuery();

        $newCount = (clone $baseQuery)
            ->where('sale_id', '>', $since)
            ->count();

        $pendingCount = Sale::query()
            ->where(fn ($q) => $q->where('order_source', 'web_cart')->orWhereNull('order_source'))
            ->where('status', 'pending')
            ->notExpired()
            ->count();

        return [
            'hasNew' => $newCount > 0,
            'newCount' => $newCount,
            'latestSaleId' => (clone $baseQuery)->max('sale_id') ?? 0,
            'pendingCount' => $pendingCount,
            'dailySales' => DashboardTodaySales::sumToday(),
            'dailySalesTrend' => DashboardTodaySales::salesTrendPercent(),
            'dailyTransactions' => DashboardTodaySales::countToday(),
            'dailyTransactionsTrend' => DashboardTodaySales::transactionsTrendPercent(),
        ];
    }

    public function applyListFilters(Builder $query, Request $request): void
    {
        $query->where('sale_date', '>=', now()->subDays(Sale::getOrderExpirationDays()));
        $this->applyStatusScope($query, $request->query('status'));

        $dateRange = (string) $request->get('date_range', AdminDateRange::PRESET_TODAY);
        if ($dateRange === AdminDateRange::PRESET_CUSTOM) {
            if ($request->filled('date_from') || $request->filled('date_to')) {
                AdminDateRange::applyDateTimeBetween(
                    $query,
                    'sale_date',
                    AdminDateRange::PRESET_CUSTOM,
                    $request->input('date_from'),
                    $request->input('date_to'),
                    storedAsUtc: true,
                );
            }
        } elseif (in_array($dateRange, [
            AdminDateRange::PRESET_TODAY,
            AdminDateRange::PRESET_WEEK,
            AdminDateRange::PRESET_MONTH,
        ], true)) {
            AdminDateRange::applyDateTimeBetween($query, 'sale_date', $dateRange, storedAsUtc: true);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sale_id', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('buyer_email', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%")
                            ->orWhere('first_surname', 'like', "%{$search}%")
                            ->orWhere('gmail', 'like', "%{$search}%");
                    });
            });
        }
    }

    public function filterLines(Request $request): array
    {
        $lines = [];
        $status = $request->query('status');
        $lines[] = in_array($status, ['cancelled', 'returned', 'all'], true)
            ? 'Estado: '.$status
            : 'Estado: confirmadas (completadas)';

        if ($request->filled('date_range')) {
            $lines[] = 'Rango: '.$request->date_range;
        }

        $from = $request->date_from ?: $request->start_date;
        $to = $request->date_to ?: $request->end_date;
        if (($from !== null && $from !== '') || ($to !== null && $to !== '')) {
            $lines[] = 'Fechas: '.($from ?: '…').' — '.($to ?: '…');
        }

        if ($request->filled('payment_method')) {
            $lines[] = 'Método de pago: '.$request->payment_method;
        }

        if ($request->filled('search')) {
            $lines[] = 'Búsqueda: '.$request->search;
        }

        return $lines;
    }

    public function historyBaseQuery(): Builder
    {
        return Sale::query()
            ->whereIn('status', ['pending', 'ready_to_pickup', 'completed'])
            ->where(fn ($q) => $q->where('order_source', 'web_cart')->orWhereNull('order_source'))
            ->where('sale_date', '>=', now()->subDays(Sale::getOrderExpirationDays()));
    }

    private function rows($sales): array
    {
        $statusLabels = [
            'pending' => 'Pendiente',
            'ready_to_pickup' => 'Por recoger',
            'completed' => 'Confirmada',
            'cancelled' => 'Rechazado',
            'refunded' => 'Reembolsada',
            'returned' => 'Devuelta',
        ];
        $paymentLabels = ['cash' => 'Efectivo', 'sinpe' => 'SINPE Móvil', 'transfer' => 'Transferencia'];

        return $sales->map(function (Sale $sale) use ($statusLabels, $paymentLabels): array {
            $customer = $sale->buyer_name ?: 'Mostrador / Sin datos';
            $customerEmail = $sale->buyer_email;

            if ($sale->client_id && $sale->client) {
                $customer = trim($sale->client->name.' '.$sale->client->first_surname.' '.($sale->client->second_surname ?: ''));
                $customerEmail = $sale->client->gmail;
            }

            return [
                'sale_id' => (int) $sale->sale_id,
                'invoice_number' => $sale->invoice_number ?? '#'.$sale->sale_id,
                'customer' => $customer,
                'customer_email' => $customerEmail,
                'sale_date_label' => $sale->adminSaleDateLabel(),
                'status' => $sale->status,
                'status_label' => $statusLabels[$sale->status] ?? $sale->status,
                'payment_method' => $sale->payment_method,
                'payment_label' => $paymentLabels[$sale->payment_method] ?? $sale->payment_method,
                'total' => (float) $sale->total,
            ];
        })->values()->all();
    }

    private function kpis(): array
    {
        return [
            'dailySales' => (float) DashboardTodaySales::sumToday(),
            'dailySalesTrend' => (float) DashboardTodaySales::salesTrendPercent(),
            'dailyTransactions' => (int) DashboardTodaySales::countToday(),
            'dailyTransactionsTrend' => (float) DashboardTodaySales::transactionsTrendPercent(),
            'refunds' => (int) Sale::where('status', 'returned')->whereDate('sale_date', today())->count(),
            'refundsTrend' => 0.0,
        ];
    }

    private function applyStatusScope(Builder $query, ?string $statusParam): void
    {
        if ($statusParam === 'cancelled') {
            $query->where('status', 'cancelled');
        } elseif ($statusParam === 'returned') {
            $query->where('status', 'returned');
        } elseif ($statusParam === 'all') {
            $query->whereIn('status', ['completed', 'cancelled', 'returned']);
        } else {
            $query->where('status', 'completed');
        }
    }
}
