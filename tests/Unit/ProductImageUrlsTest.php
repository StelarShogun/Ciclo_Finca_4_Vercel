<?php

namespace Tests\Unit;

use App\Services\Media\ProductImageUrls;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class ProductImageUrlsTest extends TestCase
{
    private function mockDisplayableMedia(): Media
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/test.jpg', 'fake-image');

        /** @var Media&MockInterface $media */
        $media = Mockery::mock(Media::class)->makePartial();
        $media->forceFill(['disk' => 'public']);
        $media->shouldReceive('getPathRelativeToRoot')->andReturn('media/test.jpg');

        return $media;
    }

    public function test_webp_card_mobile_url_prefers_768_over_thumbnail(): void
    {
        $media = $this->mockDisplayableMedia();
        $media->shouldReceive('hasGeneratedConversion')->with('webp_768')->andReturnTrue();
        $media->shouldReceive('getUrl')->with('webp_768')->andReturn('https://example.test/product-768.webp');
        $media->shouldNotReceive('hasGeneratedConversion')->with('webp_96');

        $this->assertSame(
            'https://example.test/product-768.webp',
            ProductImageUrls::webpCardMobileUrl($media)
        );
    }

    public function test_webp_card_mobile_url_falls_back_to_480(): void
    {
        $media = $this->mockDisplayableMedia();
        $media->shouldReceive('hasGeneratedConversion')->with('webp_768')->andReturnFalse();
        $media->shouldReceive('hasGeneratedConversion')->with('webp_480')->andReturnTrue();
        $media->shouldReceive('getUrl')->with('webp_480')->andReturn('https://example.test/product-480.webp');

        $this->assertSame(
            'https://example.test/product-480.webp',
            ProductImageUrls::webpCardMobileUrl($media)
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
