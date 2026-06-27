<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupTempProductImages extends Command
{
    protected $signature = 'cf4:cleanup-temp-product-images {--days= : Retention in days (default from config)}';

    protected $description = 'Remove temporary product image upload directories older than the retention period';

    public function handle(): int
    {
        $retentionDays = (int) ($this->option('days') ?: config('cf4_images.temp_retention_days', 1));
        $base = storage_path('app/temp');

        if (! is_dir($base)) {
            $this->info('No temp directory found.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($retentionDays)->format('Y-m-d');
        $removed = 0;

        foreach (File::directories($base) as $directory) {
            $folderName = basename($directory);
            if ($folderName < $cutoff) {
                File::deleteDirectory($directory);
                $removed++;
            }
        }

        $this->info("Removed {$removed} temp folder(s) older than {$retentionDays} day(s).");

        return self::SUCCESS;
    }
}
