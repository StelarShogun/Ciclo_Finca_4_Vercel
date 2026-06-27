<?php

namespace App\DTOs\Admin\ProductCatalog;

use App\Services\Admin\ProductCatalog\CatalogImportState;

final readonly class CatalogImportOptions
{
    public function __construct(
        public bool $fastImport = true,
    ) {}

    public static function default(): self
    {
        return new self(fastImport: true);
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function run(callable $callback): mixed
    {
        if (! $this->fastImport) {
            return $callback();
        }

        return CatalogImportState::runFastImport($callback);
    }

    /**
     * @param  callable(): array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int}  $callback
     * @return array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int}
     */
    public function runImportStats(callable $callback): array
    {
        /** @var array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int} */
        return $this->run($callback);
    }
}
