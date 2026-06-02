<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductDetailDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_serializes_purchasable_quantity_context(): void
    {
        $product = $this->seedActiveProduct('Stepper Product', stock: 6);

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('product.stockCurrent', 6)
                ->where('product.canBuy', true)
                ->where('product.priceFormatted', fn (string $formatted) => str_contains($formatted, '₡'))
            );
    }

    public function test_product_detail_serializes_featured_badge_and_brand(): void
    {
        $product = $this->seedActiveProduct('Featured Branded Bike', featured: true);
        $brand = Brand::create(['name' => 'Trek Test '.uniqid()]);
        $product->brands()->attach($brand->id);

        $this->get($this->productUrl($product->fresh(['brands'])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('product.isFeatured', true)
                ->where('primaryBrand.name', $brand->name)
                ->has('primaryBrand.catalogUrl')
            );
    }

    public function test_product_detail_serializes_classification_spec_chips(): void
    {
        [, $product] = $this->seedProductWithClassification('Negro');

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('tabs.hasSpecs', true)
                ->has('specs', 1)
                ->where('specs.0.dimensionLabel', 'Color')
                ->where('specs.0.value', 'Negro')
            );
    }

    public function test_product_detail_serializes_tabs_trust_and_pricing(): void
    {
        $product = $this->seedActiveProduct('Tabs Product', stock: 3);

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->has('tabs.defaultTab')
                ->where('product.stockCurrent', 3)
                ->where('orderReservationHours', fn (int $hours) => $hours >= 1)
                ->has('whatsappConsultUrl')
            );
    }

    public function test_product_detail_serializes_taxonomy_badges_for_subcategory(): void
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

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('taxonomy.parentCategory.name', 'Bicicletas Test')
                ->where('taxonomy.subcategory.name', 'MTB Test')
                ->where('taxonomy.parentCategory.url', route('clients.catalog', ['category_id' => $root->category_id]))
                ->where('taxonomy.subcategory.url', route('clients.catalog', ['category_id' => $sub->category_id]))
            );
    }

    public function test_product_detail_serializes_premium_placeholder_without_carousel(): void
    {
        $product = $this->seedActiveProduct('No Image Product');

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('product.showImagePlaceholder', true)
                ->where('product.carouselSlides', [])
            );
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

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('product.placeholderIconClass', fn (string $icon) => str_contains($icon, 'fa-bicycle'))
            );
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

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('product.placeholderIconClass', fn (string $icon) => str_contains($icon, 'fa-bicycle'))
            );
    }

    public function test_product_detail_serializes_low_stock_state(): void
    {
        $product = $this->seedActiveProduct('Low Stock Product', stock: 3);

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('product.stockLabel', 'Últimas unidades')
                ->where('product.isLowStock', true)
                ->where('product.stockCurrent', 3)
            );
    }

    public function test_product_detail_serializes_favorite_state_for_authenticated_client(): void
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

        $this->actingAs($client, 'clients')
            ->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->has('favoriteConfig.toggleUrl')
                ->where('product.isFavorite', false)
            );
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
