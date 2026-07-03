<?php

namespace App\Services\Admin\ProductCatalog;

/**
 * Scoped fast-import flag for bulk catalog import (media conversions deferred).
 */
final class CatalogImportState
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
