<?php

namespace App\Services\Admin\Reports;

use App\Models\Product;
use App\Services\Admin\CatalogMostSearchedProductsReportQuery;

final class CatalogMostSearchedProductsReportService
{
    /**
     * @return array<string,mixed>
     */
    public function payload(mixed $periodInput): array
    {
        $period = $this->normalizePeriod($periodInput);
        $rows = CatalogMostSearchedProductsReportQuery::topProducts($period, 50);
        $topRow = $rows->first();

        return [
            'period' => $period,
            'rows' => $rows
                ->map(fn (object $row): array => [
                    'product_id' => (int) $row->product_id,
                    'name' => (string) $row->name,
                    'sku' => Product::skuFromId((int) $row->product_id),
                    'hit_count' => (int) $row->hit_count,
                ])
                ->values()
                ->all(),
            'totalEvents' => (int) CatalogMostSearchedProductsReportQuery::totalEventsSince($period),
            'uniqueProducts' => (int) CatalogMostSearchedProductsReportQuery::distinctProductCount($period),
            'topProductName' => $topRow->name ?? null,
            'topProductHits' => isset($topRow->hit_count) ? (int) $topRow->hit_count : null,
            'maxHits' => max(1, (int) ($rows->max('hit_count') ?? 0)),
        ];
    }

    /** @return '7d'|'30d'|'90d' */
    private function normalizePeriod(mixed $value): string
    {
        $period = is_string($value) ? $value : '';

        return in_array($period, ['7d', '30d', '90d'], true) ? $period : '30d';
    }
}
