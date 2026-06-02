<?php

namespace App\Http\Controllers\Client\Auth;

use App\Actions\Client\Auth\ResetClientPasswordAfterRecovery;
use App\Actions\Client\Auth\SendClientRecoveryCode;
use App\Actions\Client\Auth\VerifyClientRecoveryCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Auth\ResetClientPasswordRequest;
use App\Http\Requests\Client\Auth\SendRecoveryCodeRequest;
use App\Http\Requests\Client\Auth\VerifyClientCodeRequest;
use App\Services\Client\Auth\ClientAuthSessionState;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordRecoveryController extends Controller
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
    ) {}

    public function showRecoveryForm(): Response
    {
        return Inertia::render('Client/Auth/RecoveryRequest', [
            'unregisteredRecoveryEmail' => session('unregistered_recovery_email') ? true : false,
        ]);
    }

    public function sendRecoveryCode(SendRecoveryCodeRequest $request, SendClientRecoveryCode $action)
    {
        return $action->handle($request);
    }

    public function showRecoveryVerifyForm()
    {
        $client = $this->sessionState->resolvePendingRecoveryClient();
        if (! $client) {
            $this->sessionState->clearPendingRecovery();

            return redirect()->route('clients.recovery.form')
                ->withErrors(['gmail' => 'Sesión expirada. Solicita un nuevo código.']);
        }

        $this->sessionState->syncPendingRecovery($client);
        $this->sessionState->clearPendingRegistration();

        return Inertia::render('Client/Auth/VerifyCode', [
            'isRecoveryFlow' => true,
            'destinationEmail' => session('pending_gmail') ?? session('pending_recovery_gmail'),
            'mailWarning' => session('mail_warning'),
        ]);
    }

    public function verifyRecoveryCode(VerifyClientCodeRequest $request, VerifyClientRecoveryCode $action)
    {
        return $action->handle($request);
    }

    public function showRecoveryResetForm()
    {
        $client = $this->sessionState->resolvePendingRecoveryClient();
        if (! $client || ! $this->sessionState->isRecoveryCodeVerified()) {
            $this->sessionState->clearPendingRecovery();

            return redirect()->route('clients.recovery.form');
        }

        $this->sessionState->syncPendingRecovery($client);

        return Inertia::render('Client/Auth/RecoveryReset', [
            'gmail' => $client->gmail,
        ]);
    }

    public function updateRecoveryPassword(ResetClientPasswordRequest $request, ResetClientPasswordAfterRecovery $action)
    {
        return $action->handle($request);
    }
}
