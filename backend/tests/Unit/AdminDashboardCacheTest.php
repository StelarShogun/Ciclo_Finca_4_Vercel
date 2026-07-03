<?php

namespace Tests\Unit;

use App\Support\AdminDashboardCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminDashboardCacheTest extends TestCase
{
    public function test_forget_clears_dashboard_index_and_chart_periods(): void
    {
        Cache::put(AdminDashboardCache::indexKey(), ['cached' => true], 60);
        Cache::put(AdminDashboardCache::chartKey('7d'), ['cached' => true], 60);
        Cache::put(AdminDashboardCache::chartKey('30d'), ['cached' => true], 60);
        Cache::put(AdminDashboardCache::chartKey('90d'), ['cached' => true], 60);

        AdminDashboardCache::forget();

        $this->assertFalse(Cache::has(AdminDashboardCache::indexKey()));
        $this->assertFalse(Cache::has(AdminDashboardCache::chartKey('7d')));
        $this->assertFalse(Cache::has(AdminDashboardCache::chartKey('30d')));
        $this->assertFalse(Cache::has(AdminDashboardCache::chartKey('90d')));
    }
}
