<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Admin list/report date windows using {@see config('app.timezone')}.
 *
 * Prefer whereBetween on datetime columns instead of whereDate to avoid UTC vs store-time drift.
 */
final class AdminDateRange
{
    public const PRESET_TODAY = 'today';

    public const PRESET_WEEK = 'week';

    public const PRESET_MONTH = 'month';

    public const PRESET_YEAR = 'year';

    public const PRESET_CUSTOM = 'custom';

    public static function timezone(): string
    {
        return (string) config('app.timezone', 'UTC');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function todayDateString(): string
    {
        return self::now()->toDateString();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function bounds(string $preset, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = self::now();

        return match ($preset) {
            self::PRESET_TODAY => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            self::PRESET_WEEK => [
                $now->copy()->startOfWeek(Carbon::MONDAY),
                $now->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            self::PRESET_MONTH => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            self::PRESET_YEAR => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            self::PRESET_CUSTOM => [
                self::parseDateStart($dateFrom ?? $now->toDateString()),
                self::parseDateEnd($dateTo ?? $now->toDateString()),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function boundsAsDateTimeStrings(
        string $preset,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        bool $storedAsUtc = false,
    ): array {
        [$start, $end] = $storedAsUtc
            ? self::boundsForUtcColumn($preset, $dateFrom, $dateTo)
            : self::bounds($preset, $dateFrom, $dateTo);

        return [$start->toDateTimeString(), $end->toDateTimeString()];
    }

    public static function parseDateStart(string $date): Carbon
    {
        return Carbon::parse($date, self::timezone())->startOfDay();
    }

    public static function parseDateEnd(string $date): Carbon
    {
        return Carbon::parse($date, self::timezone())->endOfDay();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function boundsForUtcColumn(string $preset, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$start, $end] = self::bounds($preset, $dateFrom, $dateTo);

        return [$start->copy()->utc(), $end->copy()->utc()];
    }

    public static function applyDateTimeBetween(
        Builder $query,
        string $column,
        string $preset,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        bool $storedAsUtc = false,
    ): void {
        [$start, $end] = $storedAsUtc
            ? self::boundsForUtcColumn($preset, $dateFrom, $dateTo)
            : self::bounds($preset, $dateFrom, $dateTo);

        $query->whereBetween($column, [$start, $end]);
    }

    public static function applyOptionalDateTimeFromTo(
        Builder $query,
        string $column,
        ?string $from,
        ?string $to,
        bool $storedAsUtc = false,
    ): void {
        if (is_string($from) && trim($from) !== '') {
            $start = self::parseDateStart($from);
            $query->where($column, '>=', $storedAsUtc ? $start->copy()->utc() : $start);
        }

        if (is_string($to) && trim($to) !== '') {
            $end = self::parseDateEnd($to);
            $query->where($column, '<=', $storedAsUtc ? $end->copy()->utc() : $end);
        }
    }

    public static function resolvePresetFromRequest(?string $dateRange, ?string $dateFrom, ?string $dateTo): ?string
    {
        if (is_string($dateRange) && $dateRange !== '') {
            return $dateRange;
        }

        if ($dateFrom || $dateTo) {
            return self::PRESET_CUSTOM;
        }

        return null;
    }
}
