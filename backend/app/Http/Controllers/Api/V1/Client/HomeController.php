<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Services\Api\PublicIdMapper;
use App\ViewModels\Client\StorefrontViewModel;
use Illuminate\Http\JsonResponse;

/**
 * Home del storefront para el SPA Next. Reusa StorefrontViewModel::home()
 * (destacados, categorías, hero). Público.
 */
final class HomeController extends Controller
{
    public function index(PublicIdMapper $publicIds): JsonResponse
    {
        return response()->json(['data' => $publicIds->map('home', StorefrontViewModel::home())]);
    }
}
