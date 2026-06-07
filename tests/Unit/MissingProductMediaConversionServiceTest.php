<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\Admin\Images\MissingProductMediaConversionService;
use App\Support\GdImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class MissingProductMediaConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_finds_and_generates_missing_webp_conversions(): void
    {
        if (! GdImage::supportsWebp()) {
            $this->markTestSkipped('GD WebP support required.');
        }

        Storage::fake('public');

        $category = Category::create([
            'name' => 'CF4 Missing WebP Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $product = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'CF4 Missing WebP Product',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 1,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $path = $this->createTempJpeg();
        $media = $product->addMedia($path)->preservingOriginal()->toMediaCollection('main_image');
        @unlink($path);

        // Conversions may run synchronously in tests; force "missing" state for the assertion.
        $media->forceFill(['generated_conversions' => []])->save();
        $media->refresh();

        $service = app(MissingProductMediaConversionService::class);

        $missing = $service->mediaIdsMissingConversions([(int) $media->id]);
        $this->assertSame([(int) $media->id], $missing);

        $result = $service->generateForMediaIds($missing);
        $this->assertSame(1, $result['processed']);
        $this->assertSame(0, $result['failed']);

        $media->refresh();
        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($media->hasGeneratedConversion('webp_480'));
        $this->assertSame([], $service->mediaIdsMissingConversions([(int) $media->id]));
    }

    private function createTempJpeg(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cf4-missing-webp-').'.jpg';
        $image = imagecreatetruecolor(40, 40);
        imagejpeg($image, $path);
        imagedestroy($image);

        return $path;
    }
}
