<?php

namespace App\Actions\Client\Auth;

use App\Http\Requests\Client\Auth\VerifyClientCodeRequest;
use App\Services\Client\Auth\ClientAuthSessionState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class VerifyClientRecoveryCode
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
    ) {}

    public function handle(VerifyClientCodeRequest $request): JsonResponse|RedirectResponse
    {
        $wantsJson = $request->ajax() || $request->wantsJson();
        $client = $this->sessionState->resolvePendingRecoveryClient();

        if (! $client) {
            $msg = 'Sesión expirada. Vuelve a solicitar el código.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg]);
            }

            return redirect()->route('clients.recovery.form')->withErrors(['gmail' => $msg]);
        }

        $this->sessionState->syncPendingRecovery($client);
        $code = $request->string('verification_code')->toString();

        if ($client->verification_code !== $code) {
            $msg = 'Código incorrecto. Inténtalo de nuevo.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg]);
            }

            return back()->withErrors(['verification_code' => $msg]);
        }

        if (now()->isAfter($client->verification_code_expires_at)) {
            $msg = 'El código ha expirado. Vuelve a solicitar la recuperación.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg]);
            }

            return redirect()->route('clients.recovery.form')->withErrors(['verification_code' => $msg]);
        }

        $client->update([
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        $this->sessionState->markRecoveryCodeVerified();

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'message' => 'Código verificado.',
                'redirect' => route('clients.recovery.reset.form'),
            ]);
        }

        return redirect()->route('clients.recovery.reset.form');
    }
}
