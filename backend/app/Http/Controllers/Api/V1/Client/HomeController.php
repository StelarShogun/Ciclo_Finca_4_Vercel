<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\ViewModels\Client\StorefrontViewModel;
use Illuminate\Http\JsonResponse;

/**
 * Home del storefront para el SPA Next. Reusa StorefrontViewModel::home()
 * (destacados, categorías, hero). Público.
 */
final class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => StorefrontViewModel::home()]);
    }
}
