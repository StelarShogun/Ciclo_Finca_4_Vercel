<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    public const KEY_ORDER_EXPIRATION_DAYS = 'order_expiration_days';

    public const KEY_READY_TO_PICKUP_EXPIRATION_DAYS = 'ready_to_pickup_expiration_days';

    public const KEY_READY_TO_PICKUP_EXPIRATION_HOURS = 'ready_to_pickup_expiration_hours';

    protected $fillable = [
        'key',
        'value',
    ];

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
        Cache::forget(self::cacheKeyReadyToPickupExpirationHours());
    }

    public static function getStoredReadyToPickupExpirationHours(): ?int
    {
        $raw = static::query()
            ->where('key', self::KEY_READY_TO_PICKUP_EXPIRATION_HOURS)
            ->value('value');

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
}
