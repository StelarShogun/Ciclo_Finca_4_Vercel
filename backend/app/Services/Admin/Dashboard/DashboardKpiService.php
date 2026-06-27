<?php

namespace App\Services\Admin\Dashboard;

use Illuminate\Http\Request;

final readonly class DashboardKpiService
{
    public function __construct(private DashboardDataService $dashboardData) {}

    public function summary(): array
    {
        return $this->dashboardData->summary();
    }

    public function jsonSummary(): array
    {
        return $this->dashboardData->jsonSummary();
    }

    public function withRequestRange(array $data, Request $request): array
    {
        return $this->dashboardData->withRequestRange($data, $request);
    }
}
