<?php

namespace App\Services\Admin\Reports;

use Carbon\CarbonInterface;

final class SalesPerformanceReportService
{
    public function __construct(
        private SalesPerformanceDateRangeService $rangeService,
        private SalesPerformanceMetricsService $metricsService,
    ) {}

    /**
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     */
    public function rangePayload(array $input): array
    {
        return $this->periodPayload($this->rangeService->resolve($input));
    }

    /**
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     */
    public function metricsPayload(array $input): array
    {
        $resolved = $this->rangeService->resolve($input);
        $current = $this->metricsService->aggregateCompletedSales(
            $resolved['current_start']->utc(),
            $resolved['current_end']->utc(),
        );
        $previous = $this->metricsService->aggregateCompletedSales(
            $resolved['previous_start']->utc(),
            $resolved['previous_end']->utc(),
        );

        return array_merge($this->periodPayload($resolved), [
            'current_metrics' => $current,
            'previous_metrics' => $previous,
            'comparison' => $this->metricsService->comparisonVersusPrior($current, $previous),
        ]);
    }

    /**
     * @param  array<string,mixed>  $resolved
     * @return array<string,mixed>
     */
    private function periodPayload(array $resolved): array
    {
        return [
            'success' => true,
            'preset' => $resolved['preset'],
            'from' => $resolved['from'],
            'to' => $resolved['to'],
            'current_period' => [
                'start' => $resolved['current_start']->toIso8601String(),
                'end' => $resolved['current_end']->toIso8601String(),
                'label' => $this->humanRangeLabel($resolved['current_start'], $resolved['current_end']),
            ],
            'previous_period' => [
                'start' => $resolved['previous_start']->toIso8601String(),
                'end' => $resolved['previous_end']->toIso8601String(),
                'label' => $this->humanRangeLabel($resolved['previous_start'], $resolved['previous_end']),
            ],
        ];
    }

    private function humanRangeLabel(CarbonInterface $start, CarbonInterface $end): string
    {
        return $start->format('d/m/Y').' - '.$end->format('d/m/Y');
    }
}
