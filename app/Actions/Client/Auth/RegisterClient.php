<?php

namespace App\Actions\Client\Auth;

use App\Http\Requests\Client\Auth\RegisterClientRequest;
use App\Models\Client;
use App\Services\Client\Auth\ClientAuthSessionState;
use App\Services\Client\Auth\ClientVerificationCodeIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

final class RegisterClient
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
        private ClientVerificationCodeIssuer $verificationCodes,
    ) {}

    public function handle(RegisterClientRequest $request): RedirectResponse
    {
        $client = Client::create([
            'name' => $request->string('name')->toString(),
            'first_surname' => $request->string('first_surname')->toString(),
            'second_surname' => $request->input('second_surname'),
            'gmail' => strtolower($request->string('gmail')->toString()),
            'password' => Hash::make($request->string('password')->toString()),
            'email_verified' => false,
        ]);

        $this->sessionState->setPendingRegistration($client);
        $this->sessionState->clearPendingRecovery();

        $mailWarning = $this->verificationCodes->assignAndSend(
            $client,
            ClientVerificationCodeIssuer::CONTEXT_REGISTRATION
        );

        return redirect()->route('clients.verify.form')
            ->with('pending_gmail', $client->gmail)
            ->with('mail_warning', $mailWarning);
    }
}
