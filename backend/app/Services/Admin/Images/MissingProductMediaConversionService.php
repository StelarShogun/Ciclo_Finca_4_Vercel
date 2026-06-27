<?php

namespace App\Services\Admin\Images;

use App\Models\Product;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\GdImage;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Finds product media missing WebP conversions and generates them.
 * Used after catalog import and by the scheduled fallback task.
 */
final class MissingProductMediaConversionService
{
    /** Storefront card size — if present, other sizes are filled with onlyMissing. */
    public const CANONICAL_CONVERSION = 'webp_480';

    /** @var list<string> */
    private const PRODUCT_COLLECTIONS = ['main_image', 'gallery'];

    public function __construct(private readonly FileManipulator $fileManipulator) {}

    /**
     * @param  list<int>|null  $onlyIds
     * @return list<int>
     */
    public function mediaIdsMissingConversions(?array $onlyIds = null, int $limit = 100): array
    {
        if (! GdImage::supportsWebp() || $limit <= 0) {
            return [];
        }

        $query = Media::query()
            ->where('model_type', Product::class)
            ->whereIn('collection_name', self::PRODUCT_COLLECTIONS)
            ->orderBy('id');

        if ($onlyIds !== null) {
            if ($onlyIds === []) {
                return [];
            }

            $query->whereIn('id', $onlyIds);
        }

        $ids = [];

        foreach ($query->cursor() as $media) {
            if (! $media instanceof Media) {
                continue;
            }

            if ($this->isMissingStorefrontConversions($media)) {
                $ids[] = (int) $media->id;
            }

            if (count($ids) >= $limit) {
                break;
            }
        }

        return $ids;
    }

    /**
     * @param  list<int>  $mediaIds
     * @return array{processed: int, failed: int}
     */
    public function generateForMediaIds(array $mediaIds): array
    {
        if (! GdImage::supportsWebp() || $mediaIds === []) {
            return ['processed' => 0, 'failed' => 0];
        }

        $processed = 0;
        $failed = 0;

        foreach (array_chunk($mediaIds, 20) as $chunk) {
            Media::query()
                ->whereIn('id', $chunk)
                ->orderBy('id')
                ->each(function (Media $media) use (&$processed, &$failed): void {
                    if (! $this->isMissingStorefrontConversions($media)) {
                        return;
                    }

                    try {
                        $this->fileManipulator->createDerivedFiles(
                            $media,
                            onlyConversionNames: [],
                            onlyMissing: true,
                            withResponsiveImages: false,
                            queueAll: false,
                        );
                        $media->refresh();

                        if ($this->isMissingStorefrontConversions($media)) {
                            $failed++;
                            Log::warning('catalog_media_conversion_incomplete', [
                                'media_id' => $media->id,
                            ]);

                            return;
                        }

                        $processed++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('catalog_media_conversion_failed', SensitiveDataMasker::exceptionContext($e, [
                            'media_id' => $media->id,
                        ]));
                    }
                });
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    public function isMissingStorefrontConversions(Media $media): bool
    {
        return ! $media->hasGeneratedConversion(self::CANONICAL_CONVERSION);
    }
}
