<?php

namespace App\ViewModels\Admin;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;

final class DashboardViewModel
{
    public static function from(array $data): array
    {
        return [
            'totalProducts' => (int) ($data['totalProducts'] ?? 0),
            'totalSuppliers' => (int) ($data['totalSuppliers'] ?? 0),
            'totalCategories' => (int) ($data['totalCategories'] ?? 0),
            'todaySales' => (float) ($data['todaySales'] ?? 0),
            'lowStockProducts' => (int) ($data['lowStockProducts'] ?? 0),
            'salesTrend' => (float) ($data['salesTrend'] ?? 0),
            'monthlySales' => (float) ($data['monthlySales'] ?? 0),
            'monthlyTrend' => (float) ($data['monthlyTrend'] ?? 0),
            'recentSales' => collect($data['recentSales'] ?? [])->map(fn (Sale $sale) => [
                'id' => $sale->sale_id,
                'invoice' => $sale->adminDashboardInvoiceLabel(),
                'client' => $sale->adminDashboardClientLabel(),
                'total' => (float) $sale->total,
                'dateShort' => $sale->adminSaleDateShortLabel(),
                'dateFull' => $sale->adminSaleDateLabel(),
                'statusClass' => $sale->adminDashboardStatusBadgeClass(),
                'statusShort' => $sale->adminDashboardStatusShortLabel(),
                'statusTitle' => $sale->adminDashboardStatusTitle(),
            ])->values()->all(),
            'lowStockList' => collect($data['lowStockProductsList'] ?? [])->map(fn (Product $product) => [
                'id' => $product->product_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category->name ?? '—',
                'stock' => (int) $product->stock_current,
            ])->values()->all(),
            'salesByDay' => collect($data['salesByDay'] ?? [])->map(fn ($row) => [
                'date' => Carbon::parse(data_get($row, 'date'))->isoFormat('D MMM'),
                'total' => (float) data_get($row, 'total', 0),
            ])->values()->all(),
            'salesRange' => $data['salesRange'] ?? 'last7',
            'salesFrom' => $data['salesFrom'] ?? null,
            'salesTo' => $data['salesTo'] ?? null,
            'productsByCategory' => collect($data['productsByCategory'] ?? [])
                ->filter(fn ($row) => (int) ($row['total'] ?? 0) > 0)
                ->map(fn ($row) => ['label' => $row['categoria'], 'total' => (int) $row['total']])
                ->values()
                ->all(),
            'topProducts' => collect($data['topProducts'] ?? [])->map(fn ($row) => [
                'name' => $row->name,
                'units' => (int) $row->total_vendido,
                'revenue' => (float) $row->ingresos,
            ])->values()->all(),
            'error' => $data['error'] ?? null,
        ];
    }

    public static function empty(): array
    {
        return self::from([
            'error' => 'No fue posible cargar los datos del dashboard.',
        ]);
    }
}
