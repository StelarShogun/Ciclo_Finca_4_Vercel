<?php

namespace App\Services\Admin;

use App\Models\Client;
use App\Support\AdminPerPage;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * CF4-33 — agregados de compras (ventas completadas) por cliente en un periodo.
 */
final class ClientPurchaseHistoryQuery
{
    public const PERIODS = ['7d', '30d', '90d'];

    public const SORTS = ['total_purchased', 'orders_count', 'avg_ticket'];

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,string>
     */
    public function indexPayload(array $filters): array
    {
        return [
            'period' => self::sanitizePeriod((string) ($filters['period'] ?? '30d')),
            'sort' => self::sanitizeSort((string) ($filters['sort'] ?? 'total_purchased')),
            'dir' => self::sanitizeDir((string) ($filters['dir'] ?? 'desc')),
            'q' => self::normalizeSearchInput(isset($filters['q']) ? (string) $filters['q'] : null),
        ];
    }

    /**
     * @param  array<string,mixed>  $queryParams
     * @return array<string,mixed>
     */
    public function showPayload(int $clientId, array $queryParams): array
    {
        $client = Client::query()->where('user_id', $clientId)->firstOrFail();
        $orders = DB::table('sales')
            ->where('client_id', $clientId)
            ->where('status', 'completed')
            ->orderByDesc('sale_date')
            ->get(['sale_id', 'invoice_number', 'sale_date', 'total']);

        $backParams = array_filter([
            'back_period' => $queryParams['back_period'] ?? null,
            'back_sort' => $queryParams['back_sort'] ?? null,
            'back_dir' => $queryParams['back_dir'] ?? null,
            'back_page' => $queryParams['back_page'] ?? null,
            'back_q' => $queryParams['back_q'] ?? null,
            'back_per_page' => $queryParams['back_per_page'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $listQuery = array_filter([
            'period' => $backParams['back_period'] ?? '30d',
            'sort' => $backParams['back_sort'] ?? null,
            'dir' => $backParams['back_dir'] ?? null,
            'page' => $backParams['back_page'] ?? null,
            'per_page' => $backParams['back_per_page'] ?? null,
            'q' => $backParams['back_q'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return [
            'clientId' => $clientId,
            'displayName' => $this->clientDisplayName($client),
            'gmail' => (string) $client->gmail,
            'orders' => $orders
                ->map(fn (object $order): array => $this->showOrderRow($order))
                ->values()
                ->all(),
            'listUrl' => route('admin.reports.client-purchases', $listQuery),
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function tablePayload(array $filters): array
    {
        $period = self::sanitizePeriod((string) $filters['period']);
        $search = self::normalizeSearchInput(isset($filters['q']) ? (string) $filters['q'] : null);
        $sort = self::sanitizeSort((string) $filters['sort']);
        $dir = self::sanitizeDir((string) $filters['dir']);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = AdminPerPage::resolve($filters['per_page'] ?? 10);

        [$start, $end] = self::periodBounds($period);

        $aggregateQuery = self::baseAggregates($start, $end, $search);
        $total = (int) DB::query()->fromSub($aggregateQuery, 'client_purchase_agg')->count();

        $sorted = self::baseAggregates($start, $end, $search);
        self::applySort($sorted, $sort, $dir);

        $rows = $sorted
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn (object $row): array => self::formatAggregateRow($row))
            ->values();

        $paginator = new LengthAwarePaginator($rows, $total, $perPage, $page, [
            'path' => route('admin.reports.client-purchases.table'),
            'pageName' => 'page',
        ]);
        $paginator->appends(array_merge($filters, ['per_page' => $perPage]));

        return [
            'success' => true,
            'period' => $period,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $search,
            'rows' => $rows,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'pagination_html' => view('components.admin.pagination', [
                'paginator' => $paginator,
                'label' => 'clientes',
                'perPageSubmit' => false,
            ])->render(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function clientOrdersPayload(int $clientId, string $period): array
    {
        $period = self::sanitizePeriod($period);
        [$start, $end] = self::periodBounds($period);

        $client = Client::query()->where('user_id', $clientId)->first();
        if (! $client) {
            return ['success' => false, 'message' => 'Cliente no encontrado.', 'status' => 404];
        }

        $orders = DB::table('sales')
            ->where('client_id', $clientId)
            ->where('status', 'completed')
            ->where('sale_date', '>=', $start)
            ->where('sale_date', '<=', $end)
            ->orderByDesc('sale_date')
            ->get(['sale_id', 'invoice_number', 'sale_date', 'total', 'status']);

        if ($orders->isEmpty()) {
            return ['success' => false, 'message' => 'Sin compras en el periodo.', 'status' => 404];
        }

        return [
            'success' => true,
            'client' => [
                'client_id' => (int) $client->user_id,
                'display_name' => $this->clientDisplayName($client),
                'gmail' => (string) $client->gmail,
            ],
            'orders' => $orders
                ->map(fn (object $order): array => $this->periodOrderRow($order))
                ->values(),
        ];
    }

    public static function sanitizePeriod(string $period): string
    {
        return in_array($period, self::PERIODS, true) ? $period : '30d';
    }

    public static function sanitizeSort(string $sort): string
    {
        return in_array($sort, self::SORTS, true) ? $sort : 'total_purchased';
    }

    public static function sanitizeDir(string $dir): string
    {
        return strtolower($dir) === 'asc' ? 'asc' : 'desc';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    /**
     * Texto de búsqueda: largo máximo, sin bytes nulos ni caracteres de control (evita pegados raros / fuzzing).
     * El LIKE usa parámetros enlazados; los wildcards % y _ del usuario se escapan en baseAggregates().
     */
    public static function normalizeSearchInput(?string $q): string
    {
        if ($q === null || $q === '') {
            return '';
        }
        $q = str_replace("\0", '', $q);
        $q = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $q) ?? '';
        $q = trim($q);

        return mb_substr($q, 0, 100);
    }

    public static function periodBounds(string $period): array
    {
        $end = Carbon::now()->endOfDay();
        $start = match ($period) {
            '7d' => Carbon::now()->copy()->subDays(6)->startOfDay(),
            '90d' => Carbon::now()->copy()->subDays(89)->startOfDay(),
            default => Carbon::now()->copy()->subDays(29)->startOfDay(),
        };

        return [$start, $end];
    }

    public static function baseAggregates(Carbon $start, Carbon $end, string $q): Builder
    {
        $query = DB::table('sales')
            ->join('client_table', 'sales.client_id', '=', 'client_table.user_id')
            ->where('sales.status', 'completed')
            ->whereNotNull('sales.client_id')
            ->where('sales.sale_date', '>=', $start)
            ->where('sales.sale_date', '<=', $end);

        if ($q !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('client_table.name', 'like', $like)
                    ->orWhere('client_table.first_surname', 'like', $like)
                    ->orWhere('client_table.gmail', 'like', $like);
            });
        }

        return $query
            ->select(
                'client_table.user_id as client_id',
                'client_table.name',
                'client_table.first_surname',
                'client_table.second_surname',
                'client_table.gmail',
                DB::raw('COUNT(sales.sale_id) as orders_count'),
                DB::raw('COALESCE(SUM(sales.total), 0) as total_purchased'),
                DB::raw('COALESCE(AVG(sales.total), 0) as avg_ticket'),
            )
            ->groupBy(
                'client_table.user_id',
                'client_table.name',
                'client_table.first_surname',
                'client_table.second_surname',
                'client_table.gmail',
            );
    }

    public static function applySort(Builder $query, string $sort, string $dir): Builder
    {
        $dir = $dir === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'orders_count' => $query->orderByRaw('COUNT(sales.sale_id) '.$dir),
            'avg_ticket' => $query->orderByRaw('AVG(sales.total) '.$dir),
            default => $query->orderByRaw('SUM(sales.total) '.$dir),
        };

        return $query->orderBy('client_table.user_id');
    }

    /**
     * @return array{
     *   client_id: int,
     *   display_name: string,
     *   gmail: string,
     *   orders_count: int,
     *   total_purchased: float,
     *   avg_ticket: float
     * }
     */
    public static function formatAggregateRow(object $row): array
    {
        $parts = array_filter([
            (string) ($row->name ?? ''),
            (string) ($row->first_surname ?? ''),
            (string) ($row->second_surname ?? ''),
        ], fn (string $p): bool => $p !== '');

        $displayName = trim(implode(' ', $parts));

        return [
            'client_id' => (int) $row->client_id,
            'display_name' => $displayName !== '' ? $displayName : (string) ($row->gmail ?? ''),
            'gmail' => (string) ($row->gmail ?? ''),
            'orders_count' => (int) $row->orders_count,
            'total_purchased' => round((float) $row->total_purchased, 2),
            'avg_ticket' => round((float) $row->avg_ticket, 2),
        ];
    }

    private function clientDisplayName(Client $client): string
    {
        $parts = array_filter([
            (string) $client->name,
            (string) ($client->first_surname ?? ''),
            (string) ($client->second_surname ?? ''),
        ], fn (string $part): bool => $part !== '');
        $displayName = trim(implode(' ', $parts));

        return $displayName !== '' ? $displayName : (string) $client->gmail;
    }

    private function showOrderRow(object $order): array
    {
        return [
            'sale_id' => (int) $order->sale_id,
            'invoice_number' => $order->invoice_number ?? ('#'.$order->sale_id),
            'sale_date' => Carbon::parse($order->sale_date, config('app.timezone'))->format('d/m/Y H:i'),
            'total' => (float) $order->total,
        ];
    }

    private function periodOrderRow(object $order): array
    {
        return [
            'sale_id' => (int) $order->sale_id,
            'invoice_number' => (string) $order->invoice_number,
            'sale_date' => Carbon::parse($order->sale_date, config('app.timezone'))->format('d/m/Y H:i'),
            'total' => round((float) $order->total, 2),
            'status' => (string) $order->status,
        ];
    }
}
