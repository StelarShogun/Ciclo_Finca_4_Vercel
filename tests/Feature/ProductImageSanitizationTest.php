<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Admin\Images\ProductImageOptimizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProductImageSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_store_returns_validation_error_when_image_sanitization_fails(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Sanitize',
            'second_surname' => null,
            'gmail' => 'sanitize-admin@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
        $parent = Category::create([
            'name' => 'Root Sanitize',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $supplier = Supplier::create([
            'name' => 'Proveedor Sanitize',
            'primary_contact' => 'Contacto',
            'phone' => '12345678',
            'email' => 'sanitize-supplier@example.com',
            'address' => 'Dirección',
            'delivery_time' => 3,
            'rating' => 4.5,
            'status' => 'active',
        ]);
        $brand = Brand::create(['name' => 'Marca Sanitize']);

        $canonicalParent = Category::canonicalRootIdByPhysicalRootId()[(int) $parent->category_id]
            ?? (int) $parent->category_id;

        $mock = Mockery::mock(ProductImageOptimizerService::class);
        $mock->shouldReceive('sanitizePath')
            ->once()
            ->andThrow(new RuntimeException('Invalid image file.'));
        $this->app->instance(ProductImageOptimizerService::class, $mock);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('products.store'), [
                'category_id' => $parent->category_id,
                'parent_category_id' => $canonicalParent,
                'supplier_id' => $supplier->supplier_id,
                'brand_id' => $brand->id,
                'name' => 'Producto Sanitize Fail',
                'description' => 'Test',
                'sale_price' => 2000,
                'purchase_price' => 1000,
                'stock_current' => 5,
                'stock_minimum' => 1,
                'status' => 'active',
                'is_featured' => false,
                'image' => $this->makePngUpload(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);

        $product = Product::query()->where('name', 'Producto Sanitize Fail')->first();
        $this->assertNotNull($product);
        $this->assertCount(0, $product->getMedia('main_image'));
    }

    private function makePngUpload(): UploadedFile
    {
        $path = sys_get_temp_dir().'/cf4-sanitize-upload-'.uniqid('', true).'.png';
        $image = imagecreatetruecolor(40, 30);
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, 'main.png', 'image/png', null, true);
    }
}
