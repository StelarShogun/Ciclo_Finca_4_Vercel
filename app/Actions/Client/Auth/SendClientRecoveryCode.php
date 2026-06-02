<?php

namespace App\Actions\Client\Auth;

use App\Http\Requests\Client\Auth\SendRecoveryCodeRequest;
use App\Models\Client;
use App\Services\Client\Auth\ClientAuthSessionState;
use App\Services\Client\Auth\ClientVerificationCodeIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class SendClientRecoveryCode
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
        private ClientVerificationCodeIssuer $verificationCodes,
    ) {}

    public function handle(SendRecoveryCodeRequest $request): JsonResponse|RedirectResponse
    {
        $wantsJson = $request->ajax() || $request->wantsJson();
        $client = Client::where('gmail', strtolower($request->string('gmail')->toString()))->first();

        if (! $client) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Correo no está registrado.',
                    'register_url' => route('clients.register.form'),
                ], 422);
            }

            return redirect()
                ->route('clients.recovery.form')
                ->withInput($request->only('gmail'))
                ->with('unregistered_recovery_email', true);
        }

        $this->sessionState->setPendingRecovery($client);
        $this->sessionState->clearPendingRegistration();
        $this->verificationCodes->assignAndSend($client, ClientVerificationCodeIssuer::CONTEXT_RECOVERY);

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'needs_verification' => true,
                'redirect' => route('clients.recovery.verify.form'),
                'message' => 'Se ha enviado un código de verificación a tu correo.',
            ]);
        }

        return redirect()->route('clients.recovery.verify.form')
            ->with('pending_gmail', $client->gmail);
    }
}
