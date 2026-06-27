<?php

namespace App\Actions\Client\Catalog;

use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ListCatalogProducts
{
    public function __construct(private BuildCatalogPage $buildCatalogPage) {}

    public function handle(Request $request): Response|HttpResponse
    {
        return $this->buildCatalogPage->handle($request);
    }
}
