<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogSuggestionsImagePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                $this->markTestSkipped('Catalog suggestions placeholder test requires MySQL.');
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database unavailable: '.$e->getMessage());
        }
    }

    public function test_product_suggestion_includes_placeholder_metadata_without_default_png_url(): void
    {
        $root = Category::create([
            'name' => 'Bicicletas',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'MTB',
            'parent_category_id' => $root->category_id,
        ]);
        $product = Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'Sugerencia Sin Foto',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $response = $this->get('/api/products/suggestions?search='.urlencode('Sugerencia'));
        $response->assertOk();
        $response->assertJsonPath('suggestions.0.id', (int) $product->product_id);
        $response->assertJsonPath('suggestions.0.uses_placeholder_image', true);
        $response->assertJsonPath('suggestions.0.placeholder_icon_class', 'fas fa-bicycle');
        $response->assertJsonPath('suggestions.0.image_url', null);
    }
}
