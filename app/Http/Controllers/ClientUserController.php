<?php

namespace App\Http\Controllers;

use App\Actions\Client\Auth\AttemptClientLogin;
use App\Actions\Client\Auth\RegisterClient;
use App\Actions\Client\Auth\ResendClientVerificationCode;
use App\Actions\Client\Auth\ResetClientPasswordAfterRecovery;
use App\Actions\Client\Auth\SendClientRecoveryCode;
use App\Actions\Client\Auth\VerifyClientEmail;
use App\Actions\Client\Auth\VerifyClientRecoveryCode;
use App\Actions\Client\Profile\UpdateClientPassword;
use App\Actions\Client\Profile\UpdateClientProfile;
use App\Http\Requests\Client\Auth\LoginClientRequest;
use App\Http\Requests\Client\Auth\RegisterClientRequest;
use App\Http\Requests\Client\Auth\ResetClientPasswordRequest;
use App\Http\Requests\Client\Auth\SendRecoveryCodeRequest;
use App\Http\Requests\Client\Auth\VerifyClientCodeRequest;
use App\Http\Requests\Client\Profile\UpdateClientPasswordRequest;
use App\Http\Requests\Client\Profile\UpdateClientProfileRequest;
use App\Services\Client\Auth\ClientAuthSessionState;
use App\Services\Client\Auth\GoogleOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClientUserController extends Controller
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
        private GoogleOAuthService $googleOAuth,
    ) {}

    public function show(): Response
    {
        $client = Auth::guard('clients')->user();

        return Inertia::render('Client/Profile/Index', [
            'profile' => [
                'name' => $client->name,
                'first_surname' => $client->first_surname,
                'second_surname' => $client->second_surname ?? '',
                'gmail' => $client->gmail,
                'provider' => $client->provider ?? 'local',
            ],
            'isGoogleOnly' => $client->provider === 'google',
            'profileFlash' => [
                'profileUpdated' => (bool) session('profile_updated'),
                'passwordUpdated' => (bool) session('password_updated'),
                'passwordDefined' => (bool) session('password_defined'),
            ],
        ]);
    }

    public function update(UpdateClientProfileRequest $request, UpdateClientProfile $action)
    {
        return $action->handle($request);
    }

    public function updatePassword(UpdateClientPasswordRequest $request, UpdateClientPassword $action)
    {
        return $action->handle($request);
    }

    public function showLoginForm(): Response
    {
        $recaptchaEnabled = (bool) config('recaptcha.site_key');
        $recaptchaSiteKey = $recaptchaEnabled
            ? (string) (config('services.recaptcha.key') ?? config('services.recaptcha.site_key') ?? '')
            : null;

        return Inertia::render('Client/Auth/Login', [
            'recaptchaSiteKey' => $recaptchaSiteKey !== '' ? $recaptchaSiteKey : null,
            'recoverySuccessModal' => session('recovery_success_modal'),
            'sessionExpired' => request()->get('session_expired') ? true : false,
        ]);
    }

    public function login(LoginClientRequest $request, AttemptClientLogin $action)
    {
        return $action->handle($request);
    }

    public function showRegisterForm(): Response
    {
        return Inertia::render('Client/Auth/Register', [
            'recaptchaSiteKey' => null,
        ]);
    }

    public function register(RegisterClientRequest $request, RegisterClient $action)
    {
        return $action->handle($request);
    }

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

    public function logout(Request $request)
    {
        Auth::guard('clients')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login.show')
            ->with('client_success_modal', [
                'kind' => 'logout',
                'authIcon' => 'signout',
                'title' => '¡Sesión cerrada!',
                'text' => 'Has cerrado sesión correctamente.',
            ]);
    }

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

    public function redirectToGoogle()
    {
        return $this->googleOAuth->redirectToProvider();
    }

    public function handleGoogleCallback(Request $request)
    {
        return $this->googleOAuth->handleCallback($request);
    }
}
