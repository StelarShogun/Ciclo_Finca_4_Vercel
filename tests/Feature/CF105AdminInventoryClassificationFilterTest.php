<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Client;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CF105AdminInventoryClassificationFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('CF105AdminInventoryClassificationFilterTest requiere MySQL.');
        }

        foreach (['admins', 'client_table', 'categories', 'suppliers', 'products', 'classification_dimensions', 'classification_values', 'classification_product'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Tabla requerida no existe: '.$table);
            }
        }
    }

    private function authenticateAdmin(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Web',
            'second_surname' => null,
            'gmail' => 'cf105-admin-web@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF105',
            'second_surname' => null,
            'gmail' => 'cf105-admin-guard@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($admin);
    }

    private function createCatalogContext(): array
    {
        $parent = Category::create([
            'name' => 'Ropa',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $subcategory = Category::create([
            'name' => 'Jerseys',
            'description' => null,
            'parent_category_id' => $parent->category_id,
        ]);

        $supplier = Supplier::create([
            'name' => 'Supplier CF105',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'supplier-cf105@example.com',
            'address' => 'Address',
            'delivery_time' => 3,
            'rating' => 4.5,
            'status' => 'active',
        ]);

        return [$parent, $subcategory, $supplier];
    }

    private function createDimensionWithValues(Category $subcategory, string $slug, string $label, array $values): array
    {
        $dimension = ClassificationDimension::create([
            'category_id' => $subcategory->category_id,
            'slug' => $slug,
            'label' => $label,
            'sort_order' => 0,
        ]);

        $createdValues = [];
        foreach ($values as $index => $value) {
            $createdValues[$value] = ClassificationValue::create([
                'classification_dimension_id' => $dimension->id,
                'value' => $value,
                'normalized_value' => ClassificationValue::normalizeStoredValue($value),
                'sort_order' => $index,
            ]);
        }

        return [$dimension, $createdValues];
    }

    private function createProduct(Category $subcategory, Supplier $supplier, string $name): Product
    {
        return Product::create([
            'category_id' => $subcategory->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => $name,
            'description' => 'Producto de prueba CF4-105',
            'image' => 'default.png',
            'sale_price' => 2000,
            'purchase_price' => 1000,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
    }

    private function attachClassification(Product $product, ClassificationDimension $dimension, ClassificationValue $value): void
    {
        DB::table('classification_product')->insert([
            'product_id' => $product->product_id,
            'classification_dimension_id' => $dimension->id,
            'classification_value_id' => $value->id,
        ]);
    }

    private function seedProductsWithClassifications(): array
    {
        [, $subcategory, $supplier] = $this->createCatalogContext();

        [$sizeDimension, $sizeValues] = $this->createDimensionWithValues($subcategory, 'size', 'Talla', ['S', 'M', 'L']);
        [$colorDimension, $colorValues] = $this->createDimensionWithValues($subcategory, 'color', 'Color', ['Rojo', 'Azul', 'Verde']);
        [$materialDimension, $materialValues] = $this->createDimensionWithValues($subcategory, 'material', 'Material', ['Algodón', 'Poliéster']);

        $productMRed = $this->createProduct($subcategory, $supplier, 'Jersey M Rojo');
        $this->attachClassification($productMRed, $sizeDimension, $sizeValues['M']);
        $this->attachClassification($productMRed, $colorDimension, $colorValues['Rojo']);
        $this->attachClassification($productMRed, $materialDimension, $materialValues['Algodón']);

        $productLBlue = $this->createProduct($subcategory, $supplier, 'Jersey L Azul');
        $this->attachClassification($productLBlue, $sizeDimension, $sizeValues['L']);
        $this->attachClassification($productLBlue, $colorDimension, $colorValues['Azul']);
        $this->attachClassification($productLBlue, $materialDimension, $materialValues['Poliéster']);

        $productMBlue = $this->createProduct($subcategory, $supplier, 'Jersey M Azul');
        $this->attachClassification($productMBlue, $sizeDimension, $sizeValues['M']);
        $this->attachClassification($productMBlue, $colorDimension, $colorValues['Azul']);
        $this->attachClassification($productMBlue, $materialDimension, $materialValues['Algodón']);

        return [$productMRed, $productLBlue, $productMBlue];
    }

    public function test_cp001_filters_products_by_size_only(): void
    {
        $this->authenticateAdmin();
        [$productMRed, $productLBlue, $productMBlue] = $this->seedProductsWithClassifications();

        $response = $this->get(route('inventory', [
            'classifications' => ['size' => ClassificationValue::normalizeStoredValue('M')],
        ]));

        $response->assertOk();
        $response->assertSee($productMRed->name);
        $response->assertSee($productMBlue->name);
        $response->assertDontSee($productLBlue->name);
    }

    public function test_cp002_filters_products_by_color_only(): void
    {
        $this->authenticateAdmin();
        [$productMRed, $productLBlue, $productMBlue] = $this->seedProductsWithClassifications();

        $response = $this->get(route('inventory', [
            'classifications' => ['color' => ClassificationValue::normalizeStoredValue('Rojo')],
        ]));

        $response->assertOk();
        $response->assertSee($productMRed->name);
        $response->assertDontSee($productLBlue->name);
        $response->assertDontSee($productMBlue->name);
    }

    public function test_cp003_filters_products_by_size_and_color_combination(): void
    {
        $this->authenticateAdmin();
        [$productMRed, $productLBlue, $productMBlue] = $this->seedProductsWithClassifications();

        $response = $this->get(route('inventory', [
            'classifications' => [
                'size' => ClassificationValue::normalizeStoredValue('L'),
                'color' => ClassificationValue::normalizeStoredValue('Azul'),
            ],
        ]));

        $response->assertOk();
        $response->assertSee($productLBlue->name);
        $response->assertDontSee($productMRed->name);
        $response->assertDontSee($productMBlue->name);
    }

    public function test_cp004_shows_clear_message_when_combination_has_no_results(): void
    {
        $this->authenticateAdmin();
        $this->seedProductsWithClassifications();

        $response = $this->get(route('inventory', [
            'classifications' => [
                'size' => ClassificationValue::normalizeStoredValue('S'),
                'color' => ClassificationValue::normalizeStoredValue('Verde'),
            ],
        ]));

        $response->assertOk();
        $response->assertSee('No hay productos para la combinación de clasificaciones seleccionada.');
    }

    public function test_cp005_clearing_filters_shows_complete_catalog_and_dynamic_dimension_options(): void
    {
        $this->authenticateAdmin();
        [$productMRed, $productLBlue, $productMBlue] = $this->seedProductsWithClassifications();

        $filtered = $this->get(route('inventory', [
            'classifications' => ['material' => ClassificationValue::normalizeStoredValue('Algodón')],
        ]));
        $filtered->assertOk();
        $filtered->assertSee('Material');
        $filtered->assertSee($productMRed->name);
        $filtered->assertSee($productMBlue->name);
        $filtered->assertDontSee($productLBlue->name);

        $cleared = $this->get(route('inventory'));
        $cleared->assertOk();
        $cleared->assertSee('Más filtros por clasificación');
        $cleared->assertSee($productMRed->name);
        $cleared->assertSee($productLBlue->name);
        $cleared->assertSee($productMBlue->name);
    }

    public function test_cp006_loads_dynamic_classification_filters_on_demand_endpoint(): void
    {
        $this->authenticateAdmin();
        $this->seedProductsWithClassifications();

        $response = $this->getJson(route('inventory.classification-filters'));
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonFragment(['slug' => 'size']);
        $response->assertJsonFragment(['slug' => 'color']);
        $response->assertJsonFragment(['slug' => 'material']);
    }
}

