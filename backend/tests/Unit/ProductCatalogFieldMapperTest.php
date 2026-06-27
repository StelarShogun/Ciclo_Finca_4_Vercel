<?php

namespace Tests\Unit;

use App\Services\Admin\ProductCatalog\ProductCatalogFieldMapper;
use PHPUnit\Framework\TestCase;

class ProductCatalogFieldMapperTest extends TestCase
{
    public function test_normalizes_spanish_csv_columns_in_any_order(): void
    {
        $row = ProductCatalogFieldMapper::normalizeRow([
            'precio_venta' => '12.500,50',
            'nombre' => 'Asiento MTB Demo',
            'proveedor' => 'Accesorios Ciclismo Pro',
            'stock_actual' => '8',
            'categoria' => 'Asientos',
            'marca' => 'Banana',
            'color' => 'Negro',
        ]);

        $this->assertSame('Asiento MTB Demo', $row['name']);
        $this->assertSame('Accesorios Ciclismo Pro', $row['supplier']);
        $this->assertSame('8', $row['stock_current']);
        $this->assertSame('Asientos', $row['category']);
        $this->assertSame('Negro', $row['classifications']['color']);
        $this->assertContains('Banana', $row['brands']);
    }

    public function test_detects_product_like_rows(): void
    {
        $this->assertTrue(ProductCatalogFieldMapper::rowLooksLikeProduct(['nombre' => 'Anfora Elite']));
        $this->assertFalse(ProductCatalogFieldMapper::rowLooksLikeProduct(['notas' => 'solo texto']));
    }
}
