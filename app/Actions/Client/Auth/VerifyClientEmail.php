<?php

namespace App\Actions\Client\Auth;

use App\Http\Requests\Client\Auth\VerifyClientCodeRequest;
use App\Models\Client;
use App\Services\Client\Auth\ClientAuthSessionState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class VerifyClientEmail
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
        private EstablishClientSession $establishClientSession,
    ) {}

    public function handle(VerifyClientCodeRequest $request): JsonResponse|RedirectResponse
    {
        $wantsJson = ! $request->header('X-Inertia') && ($request->ajax() || $request->wantsJson());
        $clientId = $this->sessionState->pendingRegistrationClientId();

        if (! $clientId) {
            $msg = 'Sesión expirada. Regístrate de nuevo.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->route('clients.register.form')
                ->withErrors(['verification_code' => $msg]);
        }

        $client = Client::find($clientId);
        $code = $request->string('verification_code')->toString();

        if (! $client || $client->verification_code !== $code) {
            $msg = 'Código incorrecto. Inténtalo de nuevo.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->withErrors(['verification_code' => $msg]);
        }

        if (now()->isAfter($client->verification_code_expires_at)) {
            $msg = 'El código ha expirado. Solicita uno nuevo.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->withErrors(['verification_code' => $msg]);
        }

        $client->update([
            'email_verified' => true,
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        $this->sessionState->clearPendingRegistration();
        $this->sessionState->clearPendingRecovery();

        $this->establishClientSession->handle($client);

        if ($wantsJson) {
            session()->flash('success', '¡Cuenta verificada y creada exitosamente!');

            return response()->json(['success' => true, 'redirect' => route('clients.catalog')]);
        }

        return redirect()->route('clients.catalog')->with('success', '¡Cuenta verificada y creada exitosamente!');
    }
}
