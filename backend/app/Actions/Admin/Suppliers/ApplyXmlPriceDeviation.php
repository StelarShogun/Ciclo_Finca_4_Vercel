<?php

namespace App\Actions\Admin\Suppliers;

use App\Services\Admin\Audit\AuditLogger;
use App\Services\Admin\Suppliers\XmlPriceDeviationApplier;
use App\Services\Shared\Security\SensitiveDataMasker;
use Illuminate\Support\Facades\Log;

final readonly class ApplyXmlPriceDeviation
{
    public function __construct(private XmlPriceDeviationApplier $applier) {}

    public function handle(array $analysis, array $validated, int $adminId): int
    {
        $selectedIds = array_map('intval', $validated['updates'] ?? []);
        $salePrices = $this->salePrices($validated['sale_prices'] ?? []);

        $toUpdate = collect($analysis['items'])
            ->filter(fn ($item) => $item['found'] &&
                ! is_null($item['product_id']) &&
                in_array((int) $item['product_id'], $selectedIds, true)
            )
            ->values()
            ->all();

        if ($toUpdate === []) {
            return 0;
        }

        $count = $this->applier->apply(
            updates: $toUpdate,
            thresholdPct: (float) $analysis['threshold_percentage'],
            xmlFileName: $analysis['file_name'],
            reason: $validated['reason'] ?? null,
            changedBy: $adminId,
            salePrices: $salePrices,
        );

        $this->logAudit((string) $analysis['file_name'], $count, $selectedIds, $salePrices);

        return $count;
    }

    private function salePrices(array $raw): array
    {
        $salePrices = [];

        foreach ($raw as $productId => $value) {
            $productId = (int) $productId;
            if ($productId > 0 && $value !== null && $value !== '') {
                $salePrices[$productId] = (float) $value;
            }
        }

        return $salePrices;
    }

    private function logAudit(string $fileName, int $count, array $selectedIds, array $salePrices): void
    {
        try {
            app(AuditLogger::class)->logAdminAction(
                'xml_price_deviation_apply',
                'supplier_orders',
                "Actualización de precios desde XML: {$fileName}. Productos actualizados: {$count}.",
                [
                    'xml_file_name' => $fileName,
                    'updated_count' => $count,
                    'selected_ids' => $selectedIds,
                    'sale_price_updates' => $salePrices,
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('Xml price deviation audit log write failed.', SensitiveDataMasker::exceptionContext($e));
        }
    }
}
