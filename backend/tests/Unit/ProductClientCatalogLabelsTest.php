<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

class ProductClientCatalogLabelsTest extends TestCase
{
    public function test_client_catalog_stock_label_agotado_when_stock_zero(): void
    {
        $p = new Product(['status' => 'active', 'stock_current' => 0]);

        $this->assertSame('Agotado', $p->clientCatalogStockLabel());
    }

    public function test_client_catalog_stock_label_agotado_when_out_of_stock_even_with_positive_stock(): void
    {
        $p = new Product(['status' => 'out_of_stock', 'stock_current' => 100]);

        $this->assertSame('Agotado', $p->clientCatalogStockLabel());
    }

    public function test_client_catalog_stock_label_no_disponible_when_inactive_with_stock(): void
    {
        $p = new Product(['status' => 'inactive', 'stock_current' => 10]);

        $this->assertSame('No disponible', $p->clientCatalogStockLabel());
    }

    public function test_client_catalog_stock_label_en_stock_when_active_and_stock_above_five(): void
    {
        $p = new Product(['status' => 'active', 'stock_current' => 6]);

        $this->assertSame('En stock', $p->clientCatalogStockLabel());
    }

    public function test_client_catalog_stock_label_ultimas_unidades_when_active_and_stock_one_to_five(): void
    {
        foreach ([1, 5] as $qty) {
            $p = new Product(['status' => 'active', 'stock_current' => $qty]);
            $this->assertSame('Últimas unidades', $p->clientCatalogStockLabel(), "expected últimas for stock {$qty}");
        }
    }

    public function test_client_catalog_stock_label_respects_activo_alias(): void
    {
        $p = new Product(['status' => 'activo', 'stock_current' => 20]);

        $this->assertSame('En stock', $p->clientCatalogStockLabel());
    }

    public function test_client_catalog_assigned_sku_trims_and_null_when_empty(): void
    {
        $this->assertNull((new Product(['sku' => null]))->clientCatalogAssignedSku());
        $this->assertNull((new Product(['sku' => '']))->clientCatalogAssignedSku());
        $this->assertNull((new Product(['sku' => '   ']))->clientCatalogAssignedSku());
        $this->assertSame('SKU-1', (new Product(['sku' => '  SKU-1  ']))->clientCatalogAssignedSku());
    }

    public function test_client_shows_low_stock_warning_only_active_one_to_five(): void
    {
        $this->assertTrue((new Product(['status' => 'active', 'stock_current' => 5]))->clientShowsLowStockWarning());
        $this->assertFalse((new Product(['status' => 'active', 'stock_current' => 6]))->clientShowsLowStockWarning());
        $this->assertFalse((new Product(['status' => 'active', 'stock_current' => 0]))->clientShowsLowStockWarning());
        $this->assertFalse((new Product(['status' => 'inactive', 'stock_current' => 3]))->clientShowsLowStockWarning());
    }
}
