<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Catalog\ListCatalogProducts;
use App\Http\Controllers\Controller;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\ViewModels\Client\StorefrontViewModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class StorefrontController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Client/Home/Index', StorefrontViewModel::home());
    }

    public function catalog(Request $request, ListCatalogProducts $action)
    {
        return $action->handle($request);
    }

    public function catalogHeartbeat()
    {
        return response()
            ->json([
                'version' => ClientStorefrontCache::catalogVersion(),
            ])
            ->header('Cache-Control', 'private, no-cache, max-age=0, must-revalidate');
    }
}
