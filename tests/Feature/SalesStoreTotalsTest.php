<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Totales de nueva venta (mostrador): subtotal, descuento y total coherentes con redondeo a 2 decimales.
 */
class SalesStoreTotalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                $this->markTestSkipped('Requiere MySQL (esquema sales / sale_items).');
            }

            foreach (['admins', 'products', 'sales', 'sale_items'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Tabla requerida no existe: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }
    }

    private function actingAsAdminPair(): AdminUser
    {
        $web = Client::create([
            'name' => 'Web',
            'first_surname' => 'Session',
            'second_surname' => null,
            'gmail' => 'web-session-sales-store@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Store',
            'second_surname' => null,
            'gmail' => 'admin-sales-store@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        Auth::guard('web')->login($web);
        Auth::guard('admin')->login($admin);

        return $admin;
    }

    public function test_store_computes_total_on_taxable_base_after_discount_without_iva(): void
    {
        $this->actingAsAdminPair();

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto totales test',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 20,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $payload = [
            'payment_method' => 'cash',
            'discount' => 10,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'quantity' => 2,
                    'precio_unitario' => 100,
                    'total' => 9999.99,
                ],
            ],
        ];

        $res = $this->postJson(route('sales.store'), $payload);
        $res->assertStatus(200)->assertJsonPath('success', true);

        $sale = Sale::query()->latest('sale_id')->first();
        $this->assertNotNull($sale);
        $this->assertEquals('200.00', (string) $sale->subtotal);
        $this->assertEquals('10.00', (string) $sale->discount);
        $this->assertEquals(190.0, round((float) $sale->subtotal - (float) $sale->discount, 2));
        $this->assertEquals('0.00', (string) $sale->iva);
        $this->assertEquals('190.00', (string) $sale->total);

        $item = SaleItem::where('sale_id', $sale->sale_id)->first();
        $this->assertEquals('200.00', (string) $item->total);
    }

    public function test_store_rejects_discount_greater_than_subtotal(): void
    {
        $this->actingAsAdminPair();

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto desc test',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 50,
            'purchase_price' => 10,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $res = $this->postJson(route('sales.store'), [
            'payment_method' => 'cash',
            'discount' => 150,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'quantity' => 1,
                    'precio_unitario' => 50,
                    'total' => 50,
                ],
            ],
        ]);

        $res->assertStatus(422)->assertJsonPath('success', false);
        $this->assertStringContainsString('descuento', strtolower($res->json('message')));
    }
}
