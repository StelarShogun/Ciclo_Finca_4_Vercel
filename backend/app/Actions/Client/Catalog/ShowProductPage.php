<?php

namespace App\Actions\Client\Catalog;

use App\Actions\Client\Product\BuildProductDetailPage;
use Illuminate\Http\Request;

final class ShowProductPage
{
    public function __construct(private BuildProductDetailPage $buildProductDetailPage) {}

    public function handle(Request $request, int $id, ?string $slug = null): array
    {
        return $this->buildProductDetailPage->payload($request, $id);
    }
}
