<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    // ── Existing keys ──────────────────────────────────────────────────────────
    public const KEY_ORDER_EXPIRATION_DAYS           = 'order_expiration_days';
    public const KEY_READY_TO_PICKUP_EXPIRATION_DAYS = 'ready_to_pickup_expiration_days';

    // ── New keys ───────────────────────────────────────────────────────────────
    public const KEY_WEEKLY_REPORT_RECIPIENTS = 'weekly_report_recipients';
    public const KEY_WEEKLY_REPORT_DAY        = 'weekly_report_day';
    public const KEY_WEEKLY_REPORT_HOUR       = 'weekly_report_hour';
    public const KEY_WEEKLY_REPORT_MINUTE     = 'weekly_report_minute';

    protected $fillable = [
        'key',
        'value',
    ];

    // ── Default values for weekly report ──────────────────────────────────────
    private const DEFAULT_WEEKLY_REPORT_DAY  = 1;  // Monday
    private const DEFAULT_WEEKLY_REPORT_HOUR = 8;  // 08:00
    private const DEFAULT_WEEKLY_REPORT_MINUTE = 0;

    // ══════════════════════════════════════════════════════════════════════════
    // Existing: order expiration days
    // ══════════════════════════════════════════════════════════════════════════

    public static function getStoredOrderExpirationDays(): ?int
    {
        $raw = static::query()
            ->where('key', self::KEY_ORDER_EXPIRATION_DAYS)
            ->value('value');

        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return (int) $raw;
    }

    public static function setOrderExpirationDays(int $days): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_ORDER_EXPIRATION_DAYS],
            ['value' => (string) $days]
        );

        Cache::forget(self::cacheKeyOrderExpirationDays());
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Existing: ready-to-pickup expiration days
    // ══════════════════════════════════════════════════════════════════════════

    public static function getStoredReadyToPickupExpirationDays(): ?int
    {
        $raw = static::query()
            ->where('key', self::KEY_READY_TO_PICKUP_EXPIRATION_DAYS)
            ->value('value');

        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return (int) $raw;
    }

    public static function setReadyToPickupExpirationDays(int $days): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_READY_TO_PICKUP_EXPIRATION_DAYS],
            ['value' => (string) $days]
        );

        Cache::forget(self::cacheKeyReadyToPickupExpirationDays());
    }

    // ══════════════════════════════════════════════════════════════════════════
    // New: weekly report recipients
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Returns the stored recipient list, or an empty array when not configured.
     *
     * @return string[]
     */
    public static function getWeeklyReportRecipients(): array
    {
        $raw = static::query()
            ->where('key', self::KEY_WEEKLY_REPORT_RECIPIENTS)
            ->value('value');

        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    /**
     * @param  string[]  $emails
     */
    public static function setWeeklyReportRecipients(array $emails): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_WEEKLY_REPORT_RECIPIENTS],
            ['value' => json_encode(array_values($emails))]
        );

        Cache::forget(self::cacheKeyWeeklyReportRecipients());
    }

    // ══════════════════════════════════════════════════════════════════════════
    // New: weekly report day
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Returns the configured day-of-week (0 = Sunday, 6 = Saturday),
     * falling back to Monday when not set.
     */
    public static function getWeeklyReportDay(): int
    {
        $raw = static::query()
            ->where('key', self::KEY_WEEKLY_REPORT_DAY)
            ->value('value');

        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return self::DEFAULT_WEEKLY_REPORT_DAY;
        }

        $day = (int) $raw;

        return ($day >= 0 && $day <= 6) ? $day : self::DEFAULT_WEEKLY_REPORT_DAY;
    }

    public static function setWeeklyReportDay(int $day): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_WEEKLY_REPORT_DAY],
            ['value' => (string) $day]
        );

        Cache::forget(self::cacheKeyWeeklyReportDay());
    }

    // ══════════════════════════════════════════════════════════════════════════
    // New: weekly report hour
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Returns the configured send hour (0–23), falling back to 08:00 when not set.
     */
    public static function getWeeklyReportHour(): int
    {
        $raw = static::query()
            ->where('key', self::KEY_WEEKLY_REPORT_HOUR)
            ->value('value');

        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return self::DEFAULT_WEEKLY_REPORT_HOUR;
        }

        $hour = (int) $raw;

        return ($hour >= 0 && $hour <= 23) ? $hour : self::DEFAULT_WEEKLY_REPORT_HOUR;
    }

    public static function setWeeklyReportHour(int $hour): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_WEEKLY_REPORT_HOUR],
            ['value' => (string) $hour]
        );

        Cache::forget(self::cacheKeyWeeklyReportHour());
    }

    // ══════════════════════════════════════════════════════════════════════════
    // New: weekly report minute
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Returns the configured send minute (0–59), falling back to 00 when not set.
     */
    public static function getWeeklyReportMinute(): int
    {
        $raw = static::query()
            ->where('key', self::KEY_WEEKLY_REPORT_MINUTE)
            ->value('value');

        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return self::DEFAULT_WEEKLY_REPORT_MINUTE;
        }

        $minute = (int) $raw;

        return ($minute >= 0 && $minute <= 59) ? $minute : self::DEFAULT_WEEKLY_REPORT_MINUTE;
    }

    public static function setWeeklyReportMinute(int $minute): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_WEEKLY_REPORT_MINUTE],
            ['value' => (string) $minute]
        );

        Cache::forget(self::cacheKeyWeeklyReportMinute());
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Cache keys
    // ══════════════════════════════════════════════════════════════════════════

    public static function cacheKeyOrderExpirationDays(): string
    {
        return 'app_settings.effective_order_expiration_days';
    }

    public static function cacheKeyReadyToPickupExpirationDays(): string
    {
        return 'app_settings.effective_ready_to_pickup_expiration_days';
    }

    public static function cacheKeyWeeklyReportRecipients(): string
    {
        return 'app_settings.weekly_report_recipients';
    }

    public static function cacheKeyWeeklyReportDay(): string
    {
        return 'app_settings.weekly_report_day';
    }

    public static function cacheKeyWeeklyReportHour(): string
    {
        return 'app_settings.weekly_report_hour';
    }

    public static function cacheKeyWeeklyReportMinute(): string
    {
        return 'app_settings.weekly_report_minute';
    }
}