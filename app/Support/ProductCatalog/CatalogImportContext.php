<?php

namespace App\Support\ProductCatalog;

use App\Data\Admin\ProductCatalog\CatalogImportOptions;
use App\Services\Admin\ProductCatalog\CatalogImportState;

/**
 * @deprecated Use {@see CatalogImportOptions} and {@see CatalogImportState}.
 */
final class CatalogImportContext
{
    public static bool $fastImport = false;

    public static function isFastImport(): bool
    {
        return CatalogImportState::isFastImport();
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function runFastImport(callable $callback): mixed
    {
        return CatalogImportState::runFastImport($callback);
    }

    /**
     * @param  callable(): array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int}  $callback
     * @return array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int}
     */
    public static function runFastImportStats(callable $callback): array
    {
        return CatalogImportOptions::default()->runImportStats($callback);
    }
}
