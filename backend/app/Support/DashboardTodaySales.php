<?php

namespace App\Support;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * CF4-160 — confirmed sales total for the admin dashboard "Ventas hoy" KPI.
 *
 * Walk-in sales use {@see sale_date}. Web encargos confirmed today use {@see updated_at}
 * because sale_date reflects when the order was placed, not when it was confirmed.
 */
final class DashboardTodaySales
{
    public static function sumToday(): float
    {
        return self::sumForPreset(AdminDateRange::PRESET_TODAY);
    }

    public static function sumYesterday(): float
    {
        $yesterday = AdminDateRange::now()->copy()->subDay();

        return self::sumForPreset(
            AdminDateRange::PRESET_CUSTOM,
            $yesterday->toDateString(),
            $yesterday->toDateString(),
        );
    }

    public static function countToday(): int
    {
        [$windowStart, $windowEnd] = AdminDateRange::boundsForUtcColumn(AdminDateRange::PRESET_TODAY);

        return self::completedSalesInWindowQuery($windowStart, $windowEnd)->count();
    }

    public static function countYesterday(): int
    {
        $yesterday = AdminDateRange::now()->copy()->subDay();
        [$windowStart, $windowEnd] = AdminDateRange::boundsForUtcColumn(
            AdminDateRange::PRESET_CUSTOM,
            $yesterday->toDateString(),
            $yesterday->toDateString(),
        );

        return self::completedSalesInWindowQuery($windowStart, $windowEnd)->count();
    }

    public static function salesTrendPercent(): float
    {
        return self::trendPercent(self::sumToday(), self::sumYesterday());
    }

    public static function transactionsTrendPercent(): float
    {
        return self::trendPercent((float) self::countToday(), (float) self::countYesterday());
    }

    public static function forgetDashboardCache(): void
    {
        AdminDashboardCache::forget();
    }

    public static function sumForPreset(string $preset, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        [$windowStart, $windowEnd] = AdminDateRange::boundsForUtcColumn($preset, $dateFrom, $dateTo);

        return (float) self::completedSalesInWindowQuery($windowStart, $windowEnd)->sum('total');
    }

    /**
     * @return Builder<Sale>
     */
    public static function completedSalesInWindowQuery(Carbon $windowStartUtc, Carbon $windowEndUtc): Builder
    {
        return Sale::query()
            ->where('status', 'completed')
            ->where(function (Builder $query) use ($windowStartUtc, $windowEndUtc) {
                $query
                    ->where(function (Builder $walkIn) use ($windowStartUtc, $windowEndUtc) {
                        $walkIn->where('order_source', 'walk_in')
                            ->whereBetween('sale_date', [$windowStartUtc, $windowEndUtc]);
                    })
                    ->orWhere(function (Builder $web) use ($windowStartUtc, $windowEndUtc) {
                        $web->where(function (Builder $source) {
                            $source->where('order_source', 'web_cart')
                                ->orWhere(function (Builder $legacyWeb) {
                                    $legacyWeb->whereNull('order_source')
                                        ->whereNotNull('client_id');
                                });
                        })->whereBetween('updated_at', [$windowStartUtc, $windowEndUtc]);
                    })
                    ->orWhere(function (Builder $legacyWalkIn) use ($windowStartUtc, $windowEndUtc) {
                        $legacyWalkIn->whereNull('order_source')
                            ->whereNull('client_id')
                            ->whereBetween('sale_date', [$windowStartUtc, $windowEndUtc]);
                    });
            });
    }

    private static function trendPercent(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
