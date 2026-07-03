<?php

namespace App\Actions\Client\Catalog;

use App\Services\Client\Catalog\ProductSuggestionService;

final class GetProductSuggestions
{
    public function __construct(private ProductSuggestionService $suggestions) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(string $search): array
    {
        return $this->suggestions->suggestions($search);
    }
}
