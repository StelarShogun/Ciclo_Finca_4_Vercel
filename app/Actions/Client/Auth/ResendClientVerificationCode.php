<?php

namespace App\Actions\Client\Auth;

use App\Models\Client;
use App\Services\Client\Auth\ClientAuthSessionState;
use App\Services\Client\Auth\ClientVerificationCodeIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ResendClientVerificationCode
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
        private ClientVerificationCodeIssuer $verificationCodes,
    ) {}

    public function handle(Request $request): JsonResponse|RedirectResponse
    {
        $wantsJson = ! $request->header('X-Inertia') && ($request->ajax() || $request->wantsJson());
        $clientId = $this->sessionState->pendingRegistrationClientId();

        if (! $clientId) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'Sesión expirada.'], 422);
            }

            return redirect()->route('clients.register.form');
        }

        $client = Client::find($clientId);
        if (! $client) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 422);
            }

            return redirect()->route('clients.register.form');
        }

        $mailWarning = $this->verificationCodes->assignAndSend(
            $client,
            ClientVerificationCodeIssuer::CONTEXT_RESEND
        );

        if ($wantsJson) {
            return response()->json(['success' => true, 'message' => 'Código reenviado correctamente.']);
        }

        return redirect()->route('clients.verify.form')
            ->with('pending_gmail', $client->gmail)
            ->with('mail_warning', $mailWarning)
            ->with('success', 'Se ha enviado un nuevo código a tu correo.');
    }
}
