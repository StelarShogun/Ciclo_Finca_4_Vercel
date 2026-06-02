<?php

namespace App\Http\Controllers\Client\Auth;

use App\Actions\Client\Auth\AttemptClientLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Auth\LoginClientRequest;
use Inertia\Inertia;
use Inertia\Response;

final class LoginController extends Controller
{
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
}
