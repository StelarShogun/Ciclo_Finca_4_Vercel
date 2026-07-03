<?php

namespace App\Jobs;

use App\Services\Admin\Images\MissingProductMediaConversionService;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Generates missing WebP conversions after a bulk catalog import.
 * Dispatched with afterResponse() so the admin UI is not blocked.
 * Any failures are picked up automatically by cf4:regenerate-missing-media-conversions.
 */
final class GenerateCatalogImportMediaConversionsJob
{
    use Dispatchable;

    /**
     * @param  list<int>  $mediaIds
     */
    public function __construct(private readonly array $mediaIds) {}

    /**
     * @return array{processed: int, failed: int}
     */
    public function handle(MissingProductMediaConversionService $service): array
    {
        if ($this->mediaIds === []) {
            return ['processed' => 0, 'failed' => 0];
        }

        return $service->generateForMediaIds($this->mediaIds);
    }
}
