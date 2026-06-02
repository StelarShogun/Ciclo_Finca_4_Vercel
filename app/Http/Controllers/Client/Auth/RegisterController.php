<?php

namespace App\Http\Controllers\Client\Auth;

use App\Actions\Client\Auth\RegisterClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Auth\RegisterClientRequest;
use Inertia\Inertia;
use Inertia\Response;

final class RegisterController extends Controller
{
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
}
