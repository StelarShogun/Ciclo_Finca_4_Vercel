<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * CF4-33 — agregados de compras (ventas completadas) por cliente en un periodo.
 */
final class ClientPurchaseHistoryQuery
{
    public const PERIODS = ['7d', '30d', '90d'];

    public const SORTS = ['total_purchased', 'orders_count', 'avg_ticket'];

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
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
}
