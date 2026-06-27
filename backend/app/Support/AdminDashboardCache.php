<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class AdminDashboardCache
{
    private const INDEX_KEY = 'cf4:admin:dashboard_index';

    private const CHART_PERIODS = ['7d', '30d', '90d'];

    public static function forget(): void
    {
        Cache::forget(self::INDEX_KEY);

        foreach (self::CHART_PERIODS as $period) {
            Cache::forget(self::chartKey($period));
        }
    }

    public static function indexKey(): string
    {
        return self::INDEX_KEY;
    }

    public static function chartKey(string $period): string
    {
        return 'cf4:admin:dashboard_charts:'.$period;
    }
}
