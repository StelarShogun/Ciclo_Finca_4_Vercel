<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Catalog\ShowProductPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class ProductPageController extends Controller
{
    public function product(Request $request, int $id, ShowProductPage $action, ?string $slug = null)
    {
        return $action->handle($request, $id, $slug);
    }
}
