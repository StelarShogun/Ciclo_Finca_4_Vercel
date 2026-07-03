<?php

namespace App\Services\Admin\Suppliers;

final readonly class XmlPriceDeviationApplier
{
    public function __construct(private XmlPriceDeviationService $service) {}

    /**
     * @param  list<array<string, mixed>>  $updates
     * @param  array<int, float>  $salePrices
     */
    public function apply(
        array $updates,
        float $thresholdPct,
        string $xmlFileName,
        ?string $reason,
        int $changedBy,
        array $salePrices = [],
    ): int {
        return $this->service->applyUpdates(
            updates: $updates,
            thresholdPct: $thresholdPct,
            xmlFileName: $xmlFileName,
            reason: $reason,
            changedBy: $changedBy,
            salePrices: $salePrices,
        );
    }
}
