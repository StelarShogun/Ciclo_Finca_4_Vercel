<?php

namespace App\Actions\Client\Catalog;

use Illuminate\Http\Request;

final class ListCatalogProducts
{
    public function __construct(private BuildCatalogPage $buildCatalogPage) {}

    public function handle(Request $request): array
    {
        return $this->buildCatalogPage->handle($request);
    }
}
