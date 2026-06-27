<?php

namespace App\Actions\Client\Catalog;

use App\Actions\Client\Product\BuildProductDetailPage;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ShowProductPage
{
    public function __construct(private BuildProductDetailPage $buildProductDetailPage) {}

    public function handle(Request $request, int $id, ?string $slug = null): Response|HttpResponse
    {
        return $this->buildProductDetailPage->handle($request, $id, $slug);
    }
}
