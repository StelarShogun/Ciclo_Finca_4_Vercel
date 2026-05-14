<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Product;
use App\Services\XmlPriceDeviationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/** XML price deviation – service and HTTP workflow. */
class CF4103XmlPriceDeviationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Returns (or creates) the test admin user. */
    private function createAdmin(): AdminUser
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

    /**
     * Returns the first active product in BD and forces known prices on it
     * inside the current transaction (rolled back automatically).
     */
    private function getProduct(): Product
    {
        return Product::create([
            'name' => 'Producto prueba XML',
            'description' => 'Producto creado para pruebas de XML deviation.',
            'purchase_price' => 100_000,
            'sale_price' => 150_000,
            'stock_current' => 10,
            'stock_minimum' => 0,
            'status' => 'active',
        ]);
    }

    /** Builds a single-item XML UploadedFile for the given product and price. */
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

    // -------------------------------------------------------------------------
    // Service – deviation detection
    // -------------------------------------------------------------------------

    /** A price increase above the threshold is flagged as a deviation. */
    public function test_detecta_desvio_cuando_precio_sube_sobre_umbral(): void
    {
        $product = $this->getProduct();
        $service = app(XmlPriceDeviationService::class);

        // +25 % sobre 100 000 → desvío con umbral 10 %
        $analysis = $service->analyse($this->xmlFile($product, 125_000), thresholdPct: 10.0);
        $item = $analysis['items'][0];

        $this->assertTrue($item['found']);
        $this->assertTrue($item['has_deviation']);
        $this->assertEquals(25_000, $item['difference_amount']);
        $this->assertEquals(25.0, $item['difference_percentage']);
    }

    /** A price change below the threshold is not flagged. */
    public function test_no_marca_desvio_cuando_cambio_es_menor_al_umbral(): void
    {
        $product = $this->getProduct();
        $service = app(XmlPriceDeviationService::class);

        // +5 % → bajo umbral 10 %
        $analysis = $service->analyse($this->xmlFile($product, 105_000), thresholdPct: 10.0);

        $this->assertFalse($analysis['items'][0]['has_deviation']);
    }

    /** When the purchase price rises, the service suggests a sale price that preserves the margin. */
    public function test_sugiere_precio_venta_cuando_costo_sube(): void
    {
        $product = $this->getProduct(); // purchase 100k, sale 150k → margen 50 %
        $service = app(XmlPriceDeviationService::class);

        // +20 % → nueva compra 120k → venta sugerida 120k × 1.5 = 180k
        $analysis = $service->analyse($this->xmlFile($product, 120_000), thresholdPct: 10.0);
        $item = $analysis['items'][0];

        $this->assertNotNull($item['suggested_sale_price']);
        $this->assertEquals(180_000.0, $item['suggested_sale_price']);
    }

    // -------------------------------------------------------------------------
    // Service – applyUpdates
    // -------------------------------------------------------------------------

    /** applyUpdates() persists the new purchase_price to the database. */
    public function test_apply_actualiza_purchase_price_en_bd(): void
    {
        $product = $this->getProduct();
        $service = app(XmlPriceDeviationService::class);
        $admin = $this->createAdmin();

        $analysis = $service->analyse($this->xmlFile($product, 130_000), thresholdPct: 10.0);

        $count = $service->applyUpdates(
            updates: $analysis['items'],
            thresholdPct: 10.0,
            xmlFileName: 'test.xml',
            reason: 'Test integración',
            changedBy: $admin->user_id,
        );

        $this->assertEquals(1, $count);
        $this->assertEquals(130_000, $product->fresh()->purchase_price);
    }

    /** applyUpdates() writes one history record per updated product. */
    public function test_apply_escribe_registro_en_historial(): void
    {
        $product = $this->getProduct();
        $service = app(XmlPriceDeviationService::class);
        $admin = $this->createAdmin();

        $analysis = $service->analyse($this->xmlFile($product, 120_000), thresholdPct: 10.0);

        $service->applyUpdates(
            updates: $analysis['items'],
            thresholdPct: 10.0,
            xmlFileName: 'proveedor.xml',
            reason: 'Alza mayo',
            changedBy: $admin->user_id,
        );

        $this->assertDatabaseHas('product_purchase_price_histories', [
            'product_id' => $product->product_id,
            'previous_price' => 100_000,
            'new_price' => 120_000,
            'xml_file_name' => 'proveedor.xml',
            'reason' => 'Alza mayo',
        ]);
    }

    // -------------------------------------------------------------------------
    // HTTP – controller
    // -------------------------------------------------------------------------

    /** The upload form redirects unauthenticated visitors. */
    public function test_upload_form_requiere_autenticacion(): void
    {
        $this->get(route('admin.supplier-orders.xml-deviation.upload'))
            ->assertRedirect();
    }

    /** A valid XML POST stores the analysis in the session and redirects to review. */
    public function test_analyse_endpoint_redirige_a_review_con_sesion(): void
    {
        $product = $this->getProduct();

        $this->actingAs($this->createAdmin(), 'admin')
            ->post(route('admin.supplier-orders.xml-deviation.analyse'), [
                'xml_file' => $this->xmlFile($product, 115_000),
                'threshold' => 10,
            ])
            ->assertRedirect(route('admin.supplier-orders.xml-deviation.review'));

        $this->assertNotNull(Session::get('xml_price_deviation_analysis'));
    }

    /** The analyse endpoint rejects requests without an attached file. */
    public function test_analyse_endpoint_falla_sin_archivo(): void
    {
        $this->actingAs($this->createAdmin(), 'admin')
            ->post(route('admin.supplier-orders.xml-deviation.analyse'), [
                'threshold' => 10,
            ])
            ->assertSessionHasErrors('xml_file');
    }
}
