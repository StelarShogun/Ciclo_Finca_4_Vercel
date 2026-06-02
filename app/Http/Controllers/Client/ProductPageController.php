<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Product\BuildProductDetailPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class ProductPageController extends Controller
{
    public function product(Request $request, int $id, BuildProductDetailPage $action, ?string $slug = null)
    {
        return $action->handle($request, $id, $slug);
    }
}
