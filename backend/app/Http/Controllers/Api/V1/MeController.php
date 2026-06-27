<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Identidad del usuario autenticado para el SPA Next.
 * Resuelve admin o cliente según el guard de sesión activo (Sanctum stateful).
 */
final class MeController extends Controller
{
    public function show(): JsonResponse
    {
        if (Auth::guard('admin')->check()) {
            return response()->json([
                'data' => ['type' => 'admin', 'user' => Auth::guard('admin')->user()],
            ]);
        }

        if (Auth::guard('clients')->check()) {
            return response()->json([
                'data' => ['type' => 'client', 'user' => Auth::guard('clients')->user()],
            ]);
        }

        return response()->json(['message' => 'No autenticado.'], 401);
    }
}
