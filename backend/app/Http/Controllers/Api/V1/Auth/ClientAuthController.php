<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Client\Auth\AttemptClientLogin;
use App\Actions\Client\Auth\RegisterClient;
use App\Actions\Client\Auth\ResendClientVerificationCode;
use App\Actions\Client\Auth\SendClientRecoveryCode;
use App\Actions\Client\Auth\VerifyClientEmail;
use App\Actions\Client\Auth\VerifyClientRecoveryCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Auth\LoginClientRequest;
use App\Http\Requests\Client\Auth\RegisterClientRequest;
use App\Http\Requests\Client\Auth\ResetClientPasswordRequest;
use App\Http\Requests\Client\Auth\SendRecoveryCodeRequest;
use App\Http\Requests\Client\Auth\VerifyClientCodeRequest;
use App\Services\Client\Auth\ClientAuthSessionState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

    /** Registro: crea el cliente (sin verificar) y envía el código a su correo. */
    public function register(RegisterClientRequest $request, RegisterClient $action): JsonResponse
    {
        ['client' => $client, 'mailWarning' => $mailWarning] = $action->register($request);

        return response()->json([
            'success' => true,
            'message' => 'Te enviamos un código de verificación a tu correo.',
            'pending_gmail' => $client->gmail,
            'mail_warning' => $mailWarning,
        ], 201);
    }

    /** Verifica el código; al validar, establece la sesión del cliente. */
    public function verify(VerifyClientCodeRequest $request, VerifyClientEmail $action): JsonResponse
    {
        $response = $action->handle($request);

        return $response instanceof JsonResponse ? $response : response()->json(['success' => true]);
    }

    public function resendCode(Request $request, ResendClientVerificationCode $action): JsonResponse
    {
        $response = $action->handle($request);

        return $response instanceof JsonResponse ? $response : response()->json(['success' => true]);
    }

    // ---- Recuperación de contraseña ----

    public function recoverySend(SendRecoveryCodeRequest $request, SendClientRecoveryCode $action): JsonResponse
    {
        $response = $action->handle($request);

        return $response instanceof JsonResponse
            ? $response
            : response()->json(['success' => true, 'message' => 'Si el correo existe, te enviamos un código.']);
    }

    public function recoveryVerify(VerifyClientCodeRequest $request, VerifyClientRecoveryCode $action): JsonResponse
    {
        $response = $action->handle($request);

        return $response instanceof JsonResponse ? $response : response()->json(['success' => true]);
    }

    public function recoveryReset(ResetClientPasswordRequest $request, ClientAuthSessionState $sessionState): JsonResponse
    {
        $client = $sessionState->resolvePendingRecoveryClient();
        if (! $client || ! $sessionState->isRecoveryCodeVerified()) {
            $sessionState->clearPendingRecovery();

            return response()->json(['message' => 'Sesión expirada. Volvé a intentar la recuperación.'], 422);
        }

        $sessionState->syncPendingRecovery($client);
        $client->update([
            'password' => Hash::make($request->string('new_password')->toString()),
            'provider' => 'local',
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);
        $sessionState->clearPendingRecovery();

        return response()->json(['success' => true, 'message' => 'Contraseña actualizada. Ya podés iniciar sesión.']);
    }
}
