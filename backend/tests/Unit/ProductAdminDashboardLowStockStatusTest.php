<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductAdminDashboardLowStockStatusTest extends TestCase
{
    #[Test]
    public function percent_of_minimum_returns_zero_when_minimum_not_configured(): void
    {
        $this->assertSame(0, Product::adminDashboardLowStockPercentOfMinimum(3, 0));
    }

    #[Test]
    #[DataProvider('dashboardLowStockLabelProvider')]
    public function dashboard_low_stock_status_label_uses_spanish_words_not_percentages(
        int $stockCurrent,
        int $stockMinimum,
        string $expectedLabel,
        string $expectedBadgeClass,
    ): void {
        $product = new Product([
            'stock_current' => $stockCurrent,
            'stock_minimum' => $stockMinimum,
        ]);

        $this->assertSame($expectedLabel, $product->adminDashboardLowStockStatusLabel());
        $this->assertSame($expectedBadgeClass, $product->adminDashboardLowStockStatusBadgeClass());
        $this->assertStringNotContainsString('%', $product->adminDashboardLowStockStatusLabel());
    }

    public static function dashboardLowStockLabelProvider(): array
    {
        return [
            'sin stock' => [0, 10, 'Sin stock', 'danger'],
            'critico at 25 percent' => [2, 8, 'Crítico', 'danger'],
            'muy bajo at 50 percent' => [5, 10, 'Muy bajo', 'danger'],
            'stock bajo above half minimum' => [8, 10, 'Stock bajo', 'warning'],
            'stock bajo at minimum' => [10, 10, 'Stock bajo', 'warning'],
            'no minimum configured' => [2, 0, 'Stock bajo', 'warning'],
        ];
    }

    #[Test]
    public function dashboard_low_stock_status_title_includes_percent_detail(): void
    {
        $product = new Product([
            'stock_current' => 2,
            'stock_minimum' => 10,
        ]);

        $this->assertStringContainsString('20%', $product->adminDashboardLowStockStatusTitle());
        $this->assertStringContainsString('Crítico', $product->adminDashboardLowStockStatusTitle());
    }
}
