<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use App\Services\Client\Auth\GoogleOAuthService;
use Illuminate\Http\Request;

final class GoogleOAuthController extends Controller
{
    public function __construct(
        private GoogleOAuthService $googleOAuth,
    ) {}

    public function redirectToGoogle()
    {
        return $this->googleOAuth->redirectToProvider();
    }

    public function handleGoogleCallback(Request $request)
    {
        return $this->googleOAuth->handleCallback($request);
    }
}
