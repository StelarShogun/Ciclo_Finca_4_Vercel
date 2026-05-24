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
}
