<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Product\BuildProductDetailPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class ProductPageController extends Controller
{
    public function product(Request $request, int $id, ?string $slug, BuildProductDetailPage $action)
    {
        return $action->handle($request, $id, $slug);
    }
}
