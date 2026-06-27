<?php

namespace App\Services\Admin\Dashboard;

final readonly class DashboardChartService
{
    public function __construct(private DashboardDataService $dashboardData) {}

    public function chartData(string $period): array
    {
        return $this->dashboardData->chartData($period);
    }
}
