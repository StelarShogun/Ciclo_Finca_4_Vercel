<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AppSetting extends Model
{
    public const KEY_ORDER_EXPIRATION_DAYS = 'order_expiration_days';
    public const KEY_READY_TO_PICKUP_EXPIRATION_DAYS = 'ready_to_pickup_expiration_days';
    public const KEY_READY_TO_PICKUP_EXPIRATION_HOURS = 'ready_to_pickup_expiration_hours';

    public const KEY_WEEKLY_REPORT_RECIPIENTS = 'weekly_report_recipients';
    public const KEY_WEEKLY_REPORT_DAY = 'weekly_report_day';
    public const KEY_WEEKLY_REPORT_HOUR = 'weekly_report_hour';
    public const KEY_WEEKLY_REPORT_MINUTE = 'weekly_report_minute';

    protected $fillable = [
        'key',
        'value',
    ];

    private const DEFAULT_WEEKLY_REPORT_DAY = 1;
    private const DEFAULT_WEEKLY_REPORT_HOUR = 8;
    private const DEFAULT_WEEKLY_REPORT_MINUTE = 0;

    private static function getSettingValue(string $key): ?string
    {
        try {
            if (! Schema::hasTable((new static())->getTable())) {
                return null;
            }

            return static::query()
                ->where('key', $key)
                ->value('value');
        } catch (Throwable) {
            return null;
        }
    }

    public static function getStoredOrderExpirationDays(): ?int
    {
        $raw = self::getSettingValue(self::KEY_ORDER_EXPIRATION_DAYS);

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

    public static function getStoredReadyToPickupExpirationDays(): ?int
    {
        $raw = self::getSettingValue(self::KEY_READY_TO_PICKUP_EXPIRATION_DAYS);

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
        Cache::forget(self::cacheKeyReadyToPickupExpirationHours());
    }

    public static function getStoredReadyToPickupExpirationHours(): ?int
    {
        $raw = self::getSettingValue(self::KEY_READY_TO_PICKUP_EXPIRATION_HOURS);

        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return (int) $raw;
    }

    public static function setReadyToPickupExpirationHours(int $hours): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_READY_TO_PICKUP_EXPIRATION_HOURS],
            ['value' => (string) $hours]
        );

        Cache::forget(self::cacheKeyReadyToPickupExpirationHours());
        Cache::forget(self::cacheKeyReadyToPickupExpirationDays());
    }

    public static function getWeeklyReportRecipients(): array
    {
        $raw = self::getSettingValue(self::KEY_WEEKLY_REPORT_RECIPIENTS);

        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    public static function setWeeklyReportRecipients(array $emails): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_WEEKLY_REPORT_RECIPIENTS],
            ['value' => json_encode(array_values($emails))]
        );

        Cache::forget(self::cacheKeyWeeklyReportRecipients());
    }

    public static function getWeeklyReportDay(): int
    {
        $raw = self::getSettingValue(self::KEY_WEEKLY_REPORT_DAY);

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

    public static function getWeeklyReportHour(): int
    {
        $raw = self::getSettingValue(self::KEY_WEEKLY_REPORT_HOUR);

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

    public static function getWeeklyReportMinute(): int
    {
        $raw = self::getSettingValue(self::KEY_WEEKLY_REPORT_MINUTE);

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

    public static function cacheKeyOrderExpirationDays(): string
    {
        return 'app_settings.effective_order_expiration_days';
    }

    public static function cacheKeyReadyToPickupExpirationDays(): string
    {
        return 'app_settings.effective_ready_to_pickup_expiration_days';
    }

    public static function cacheKeyReadyToPickupExpirationHours(): string
    {
        return 'app_settings.effective_ready_to_pickup_expiration_hours';
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