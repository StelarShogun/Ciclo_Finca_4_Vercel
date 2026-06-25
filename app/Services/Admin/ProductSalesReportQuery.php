<?php

namespace App\Services\Admin;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class ProductSalesReportQuery
{
    public static function base(Carbon $start, string $q, ?Carbon $end = null): Builder
    {
        $skuExpr = "CONCAT('BK-', LPAD(products.product_id, 3, '0'))";

        $query = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.sale_date', '>=', $start)
            ->when($end !== null, fn ($builder) => $builder->where('sales.sale_date', '<=', $end))
            ->select(
                'products.product_id',
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('SUM(sale_items.total) as revenue'),
            )
            ->groupBy('products.product_id', 'products.name', 'products.sku');

        if ($q !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->havingRaw(
                "(products.name LIKE ? OR {$skuExpr} LIKE ? OR COALESCE(products.sku, '') LIKE ?)",
                [$like, $like, $like]
            );
        }

        return $query;
    }

    /**
     * @return array{product_id: int, name: string, sku: string, units_sold: int, revenue: float}
     */
    public static function formatRow(object $row): array
    {
        return [
            'product_id' => (int) $row->product_id,
            'name' => (string) $row->name,
            'sku' => (isset($row->sku) && is_string($row->sku) && trim($row->sku) !== '')
                ? trim($row->sku)
                : Product::skuFromId((int) $row->product_id),
            'units_sold' => (int) $row->units_sold,
            'revenue' => round((float) $row->revenue, 2),
        ];
    }
}
