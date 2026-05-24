<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_shows_stepper_and_max_hint(): void
    {
        $product = $this->seedActiveProduct('Stepper Product', stock: 6);

        $response = $this->get($this->productUrl($product));
        $response->assertOk();
        $response->assertSee('id="product-quantity"', false);
        $response->assertSee('id="decrease-qty"', false);
        $response->assertSee('id="increase-qty"', false);
        $response->assertSee('id="product-qty-max-hint"', false);
        $response->assertSee('Máximo disponible: 6 unidades', false);
        $response->assertSee('product-detail-qty-stepper', false);
    }

    public function test_product_detail_shows_featured_badge_and_brand(): void
    {
        $product = $this->seedActiveProduct('Featured Branded Bike', featured: true);
        $brand = Brand::create(['name' => 'Trek Test '.uniqid()]);
        $product->brands()->attach($brand->id);

        $response = $this->get($this->productUrl($product->fresh(['brands'])));
        $response->assertOk();
        $response->assertSee('Destacado', false);
        $response->assertSee($brand->name, false);
        $response->assertSee('product-badge--brand', false);
        $response->assertSee('product-badge--featured', false);
        $response->assertSee('fa-star', false);
    }

    public function test_product_detail_shows_classification_spec_chips(): void
    {
        [$sub, $product, $value] = $this->seedProductWithClassification('Negro');

        $response = $this->get($this->productUrl($product));
        $response->assertOk();
        $response->assertSee('product-detail-spec-chip', false);
        $response->assertSee('Color', false);
        $response->assertSee('Negro', false);
        $response->assertSee('Características técnicas', false);
    }

    public function test_product_detail_shows_tabs_trust_and_subtotal(): void
    {
        $product = $this->seedActiveProduct('Tabs Product', stock: 3);

        $response = $this->get($this->productUrl($product));
        $response->assertOk();
        $response->assertSee('id="product-detail-tabs"', false);
        $response->assertSee('id="product-qty-subtotal"', false);
        $response->assertSee('Subtotal:', false);
        $response->assertSee('product-detail-trust', false);
        $response->assertSee('Pago al retirar', false);
        $response->assertSee('product-detail-purchase-panel', false);
    }

    public function test_product_detail_shows_taxonomy_badges_for_subcategory(): void
    {
        $root = Category::create([
            'name' => 'Bicicletas Test',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'MTB Test',
            'description' => null,
            'parent_category_id' => $root->category_id,
        ]);
        $product = $this->seedActiveProduct('MTB Detail', categoryId: $sub->category_id);

        $response = $this->get($this->productUrl($product));
        $response->assertOk();
        $response->assertSee('product-badge--category', false);
        $response->assertSee('product-badge--subcategory', false);
        $response->assertSee('Bicicletas Test', false);
        $response->assertSee('MTB Test', false);
        $response->assertSee(route('clients.catalog', ['category_id' => $root->category_id]), false);
        $response->assertSee(route('clients.catalog', ['category_id' => $sub->category_id]), false);
    }

    public function test_product_detail_shows_premium_placeholder_without_thumbs(): void
    {
        $product = $this->seedActiveProduct('No Image Product');

        $response = $this->get($this->productUrl($product));
        $response->assertOk();
        $response->assertSee('product-image-placeholder', false);
        $response->assertSee('Imagen no disponible', false);
        $response->assertSee('Próximamente fotografía del producto', false);
        $response->assertDontSee('id="product-detail-thumbs"', false);
        $response->assertDontSee('id="carousel-track"', false);
    }

    public function test_product_detail_placeholder_uses_bicycle_icon_for_mtb_category(): void
    {
        $root = Category::create([
            'name' => 'Bicicletas',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'MTB',
            'parent_category_id' => $root->category_id,
        ]);
        $product = $this->seedActiveProduct('MTB Sin Foto', categoryId: $sub->category_id);

        $response = $this->get($this->productUrl($product));
        $response->assertOk();
        $response->assertSee('fa-bicycle', false);
    }

    public function test_product_detail_placeholder_uses_parent_icon_when_subcategory_name_is_generic(): void
    {
        $root = Category::create([
            'name' => 'Bicicletas',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'Ruta / Gravel',
            'parent_category_id' => $root->category_id,
        ]);
        $product = $this->seedActiveProduct('Gravel Sin Foto', categoryId: $sub->category_id);

        $response = $this->get($this->productUrl($product));
        $response->assertOk();
        $response->assertSee('fa-bicycle', false);
    }

    public function test_product_detail_shows_low_stock_badge(): void
    {
        $product = $this->seedActiveProduct('Low Stock Product', stock: 3);

        $response = $this->get($this->productUrl($product));
        $response->assertOk();
        $response->assertSee('product-badge--low-stock', false);
        $response->assertSee('Últimas unidades', false);
        $response->assertSee('fa-exclamation-triangle', false);
    }

    public function test_product_detail_shows_favorite_button_markup(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'cliente-pdp-fav@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
        $product = $this->seedActiveProduct('Favorite Product');

        $response = $this->actingAs($client, 'clients')->get($this->productUrl($product));
        $response->assertOk();
        $response->assertSee('product-detail-favorite', false);
        $response->assertSee('product-detail-favorite__label', false);
    }

    private function productUrl(Product $product): string
    {
        return route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]);
    }

    private function seedActiveProduct(
        string $name,
        ?int $categoryId = null,
        int $stock = 10,
        bool $featured = false
    ): Product {
        return Product::create([
            'category_id' => $categoryId,
            'supplier_id' => null,
            'name' => $name,
            'description' => 'Descripción de prueba para detalle.',
            'image' => 'default.png',
            'sale_price' => 1500000,
            'purchase_price' => 800000,
            'stock_current' => $stock,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => $featured,
        ]);
    }

    /**
     * @return array{Category, Product, ClassificationValue}
     */
    private function seedProductWithClassification(string $colorValue): array
    {
        $root = Category::create([
            'name' => 'Root '.uniqid(),
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'Sub '.uniqid(),
            'parent_category_id' => $root->category_id,
        ]);
        $dim = ClassificationDimension::create([
            'category_id' => $sub->category_id,
            'slug' => 'color',
            'label' => 'Color',
            'sort_order' => 0,
        ]);
        $value = ClassificationValue::create([
            'classification_dimension_id' => $dim->id,
            'value' => $colorValue,
            'normalized_value' => ClassificationValue::normalizeStoredValue($colorValue),
            'sort_order' => 0,
        ]);
        $product = $this->seedActiveProduct('Classified Product', categoryId: $sub->category_id);
        $product->classificationValues()->attach($value->id, [
            'classification_dimension_id' => $dim->id,
        ]);

        return [$sub, $product, $value];
    }
}
