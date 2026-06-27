<?php

namespace App\Services\Admin\Dashboard;

final readonly class DashboardRecentActivityService
{
    public function __construct(private DashboardDataService $dashboardData) {}

    public function payload(): array
    {
        $summary = $this->dashboardData->summary();

        return [
            'recentSales' => $summary['recentSales'] ?? [],
            'lowStockProductsList' => $summary['lowStockProductsList'] ?? [],
            'topProducts' => $summary['topProducts'] ?? [],
            'topSuppliers' => $summary['topSuppliers'] ?? [],
        ];
    }
}
