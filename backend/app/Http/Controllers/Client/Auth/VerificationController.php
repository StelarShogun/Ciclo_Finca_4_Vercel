<?php

namespace App\Http\Controllers\Client\Auth;

use App\Actions\Client\Auth\ResendClientVerificationCode;
use App\Actions\Client\Auth\VerifyClientEmail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Auth\VerifyClientCodeRequest;
use App\Services\Client\Auth\ClientAuthSessionState;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class VerificationController extends Controller
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
    ) {}

    public function showVerifyForm()
    {
        if (! $this->sessionState->pendingRegistrationClientId()) {
            return redirect()->route('clients.register.form');
        }

        $this->sessionState->clearPendingRecovery();

        return Inertia::render('Client/Auth/VerifyCode', [
            'isRecoveryFlow' => false,
            'destinationEmail' => $this->sessionState->pendingRegistrationEmail(),
            'mailWarning' => session('mail_warning'),
        ]);
    }

    public function verify(VerifyClientCodeRequest $request, VerifyClientEmail $action)
    {
        return $action->handle($request);
    }

    public function resendCode(Request $request, ResendClientVerificationCode $action)
    {
        return $action->handle($request);
    }
}
