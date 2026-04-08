<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    public const KEY_ORDER_EXPIRATION_DAYS = 'order_expiration_days';

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

    public static function cacheKeyOrderExpirationDays(): string
    {
        return 'app_settings.effective_order_expiration_days';
    }
}
