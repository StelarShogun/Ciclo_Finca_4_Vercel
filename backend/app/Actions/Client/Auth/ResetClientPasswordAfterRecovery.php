<?php

namespace App\Actions\Client\Auth;

use App\Http\Requests\Client\Auth\ResetClientPasswordRequest;
use App\Services\Client\Auth\ClientAuthSessionState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

final class ResetClientPasswordAfterRecovery
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
    ) {}

    public function handle(ResetClientPasswordRequest $request): RedirectResponse
    {
        $client = $this->sessionState->resolvePendingRecoveryClient();

        if (! $client || ! $this->sessionState->isRecoveryCodeVerified()) {
            $this->sessionState->clearPendingRecovery();

            return redirect()->route('clients.recovery.form')
                ->withErrors(['new_password' => 'Sesión expirada. Vuelve a intentar la recuperación.']);
        }

        $this->sessionState->syncPendingRecovery($client);

        $client->update([
            'password' => Hash::make($request->string('new_password')->toString()),
            'provider' => 'local',
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        $this->sessionState->clearPendingRecovery();

        return redirect()->route('login.show')
            ->with('recovery_success_modal', 'Contraseña actualizada. Ya puedes iniciar sesión.');
    }
}
