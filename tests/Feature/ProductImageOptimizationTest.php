<?php

namespace Tests\Feature;

use App\Services\Admin\Images\ProductImageOptimizerService;
use Tests\TestCase;

class ProductImageOptimizationTest extends TestCase
{
    public function test_sanitize_path_rebuilds_jpeg_and_produces_output_file(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $service = app(ProductImageOptimizerService::class);

        $tempDir = storage_path('app/temp/'.now()->format('Y-m-d'));
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $source = $tempDir.'/cf4-test-source.png';
        $image = \imagecreatetruecolor(120, 80);
        \imagepng($image, $source);
        \imagedestroy($image);

        $sanitized = $service->sanitizePath($source);

        $this->assertFileExists($sanitized);
        $this->assertGreaterThan(0, filesize($sanitized));

        @unlink($sanitized);
    }

    public function test_sanitize_path_rejects_non_image_payload(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $service = app(ProductImageOptimizerService::class);

        $tempDir = storage_path('app/temp/'.now()->format('Y-m-d'));
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $fake = $tempDir.'/cf4-fake-image.jpg';
        file_put_contents($fake, 'not-an-image');

        $this->expectException(\RuntimeException::class);
        $service->sanitizePath($fake);

        @unlink($fake);
    }

    public function test_supports_webp_reports_gd_capability(): void
    {
        $service = app(ProductImageOptimizerService::class);

        $this->assertSame(function_exists('imagewebp'), $service->supportsWebp());
    }
}
