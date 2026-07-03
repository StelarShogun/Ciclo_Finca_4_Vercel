<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** API v1 admin: flujo XML de desviación de precios (analyse → apply). */
class XmlPriceDeviationApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'admin@ciclofinca.com'],
            [
                'name' => 'Administrator',
                'first_surname' => 'System',
                'second_surname' => null,
                'password' => bcrypt('Admin2024!@#'),
                'last_access' => null,
            ]
        );
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Producto prueba XML API',
            'description' => 'Producto para pruebas del flujo XML por API.',
            'purchase_price' => 100_000,
            'sale_price' => 150_000,
            'stock_current' => 10,
            'stock_minimum' => 0,
            'status' => 'active',
        ]);
    }

    private function xmlFile(Product $product, float $price): UploadedFile
    {
        $code = 'BK-'.$product->product_id;
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<items>
    <item>
        <code>{$code}</code>
        <name>{$product->name}</name>
        <quantity>5</quantity>
        <unit_price>{$price}</unit_price>
    </item>
</items>";

        $tmp = tempnam(sys_get_temp_dir(), 'xml_');
        file_put_contents($tmp, $xml);

        return new UploadedFile($tmp, 'test.xml', 'text/xml', UPLOAD_ERR_OK, true);
    }

    public function test_analyse_requiere_autenticacion(): void
    {
        $this->postJson('/api/v1/admin/supplier-orders/xml-deviation/analyse')->assertStatus(401);
    }

    public function test_analyse_devuelve_analysis_id_e_items(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $product = $this->product();

        $res = $this->postJson('/api/v1/admin/supplier-orders/xml-deviation/analyse', [
            'xml_file' => $this->xmlFile($product, 130_000),
            'threshold' => 10,
        ])->assertOk();

        $this->assertNotEmpty($res->json('data.analysisId'));
        $items = $res->json('data.analysis.items');
        $this->assertCount(1, $items);
        $this->assertTrue($items[0]['has_deviation']);
        $this->assertSame($product->product_id, $items[0]['product_id']);
    }

    public function test_apply_actualiza_purchase_price(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $product = $this->product();

        $analysisId = $this->postJson('/api/v1/admin/supplier-orders/xml-deviation/analyse', [
            'xml_file' => $this->xmlFile($product, 130_000),
            'threshold' => 10,
        ])->json('data.analysisId');

        $this->postJson('/api/v1/admin/supplier-orders/xml-deviation/apply', [
            'analysis_id' => $analysisId,
            'updates' => [$product->product_id],
            'sale_prices' => [],
            'reason' => 'Prueba API',
        ])->assertOk()->assertJsonPath('data.updated', 1);

        $this->assertSame(130_000.0, (float) $product->fresh()->purchase_price);
    }

    public function test_apply_con_analysis_id_invalido_devuelve_410(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->postJson('/api/v1/admin/supplier-orders/xml-deviation/apply', [
            'analysis_id' => 'no-existe',
            'updates' => [],
        ])->assertStatus(410);
    }
}
