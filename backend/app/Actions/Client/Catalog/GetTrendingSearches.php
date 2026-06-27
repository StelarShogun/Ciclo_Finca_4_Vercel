<?php

namespace App\Actions\Client\Catalog;

use App\Services\Client\Catalog\SearchTrendingService;

final class GetTrendingSearches
{
    public function __construct(private SearchTrendingService $trending) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(string $period, int $limit): array
    {
        return $this->trending->payload($period, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function periodMeta(string $period): array
    {
        return $this->trending->periodMeta($period);
    }
}
