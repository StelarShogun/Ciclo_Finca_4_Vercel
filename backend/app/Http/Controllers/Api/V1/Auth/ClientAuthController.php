<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Client\Auth\AttemptClientLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Auth\LoginClientRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Auth de cliente para el SPA Next (Sanctum cookie).
 * Reutiliza AttemptClientLogin, que ya devuelve JSON y maneja baneo +
 * verificación de correo. El SPA debe pedir antes GET /sanctum/csrf-cookie.
 */
final class ClientAuthController extends Controller
{
    public function login(LoginClientRequest $request, AttemptClientLogin $action): JsonResponse
    {
        // AttemptClientLogin entra a su rama JSON cuando wantsJson y no hay X-Inertia.
        $response = $action->handle($request);

        return $response instanceof JsonResponse
            ? $response
            : response()->json(['success' => true]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('clients')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
