<?php

namespace App\Console\Commands;

use App\Services\Admin\Images\MissingProductMediaConversionService;
use Illuminate\Console\Command;

class RegenerateMissingMediaConversionsCommand extends Command
{
    protected $signature = 'cf4:regenerate-missing-media-conversions
                            {--limit=120 : Max media rows to process per run}
                            {--dry-run : List how many would be processed without converting}';

    protected $description = 'Generate missing WebP conversions for product images (automatic fallback after imports)';

    public function handle(MissingProductMediaConversionService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $mediaIds = $service->mediaIdsMissingConversions(limit: $limit);

        if ($mediaIds === []) {
            $this->info('No product media missing WebP conversions.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf('Would process %d media item(s) missing WebP conversions.', count($mediaIds)));

            return self::SUCCESS;
        }

        $result = $service->generateForMediaIds($mediaIds);

        $this->info(sprintf(
            'Processed %d media item(s); %d could not be completed (will retry on next run).',
            $result['processed'],
            $result['failed'],
        ));

        return self::SUCCESS;
    }
}
