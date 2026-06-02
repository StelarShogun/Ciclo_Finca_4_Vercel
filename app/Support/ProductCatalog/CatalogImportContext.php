<?php

namespace App\Support\ProductCatalog;

/**
 * Runtime flags for bulk catalog import (ZIP/CSV/XML).
 */
final class CatalogImportContext
{
    public static bool $fastImport = false;

    public static function isFastImport(): bool
    {
        return self::$fastImport;
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function runFastImport(callable $callback): mixed
    {
        $previous = self::$fastImport;
        self::$fastImport = true;

        try {
            return $callback();
        } finally {
            self::$fastImport = $previous;
        }
    }

    /**
     * @param  callable(): array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int}  $callback
     * @return array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int}
     */
    public static function runFastImportStats(callable $callback): array
    {
        /** @var array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int} */
        return self::runFastImport($callback);
    }
}
