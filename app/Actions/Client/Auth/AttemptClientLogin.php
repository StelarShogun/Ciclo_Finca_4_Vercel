<?php

namespace App\Actions\Client\Auth;

use App\Http\Requests\Client\Auth\LoginClientRequest;
use App\Models\Client;
use App\Services\Client\Auth\ClientAuthSessionState;
use App\Services\Client\Auth\ClientVerificationCodeIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class AttemptClientLogin
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
        private ClientVerificationCodeIssuer $verificationCodes,
        private EstablishClientSession $establishClientSession,
    ) {}

    public function handle(LoginClientRequest $request): JsonResponse|RedirectResponse
    {
<<<<<<< Updated upstream
        $wantsJson = ($request->ajax() || $request->wantsJson()) && ! $request->header('X-Inertia');
=======
        $wantsJson = ! $request->header('X-Inertia') && ($request->ajax() || $request->wantsJson());
>>>>>>> Stashed changes
        $credentials = [
            'gmail' => $request->string('gmail')->toString(),
            'password' => $request->string('password')->toString(),
        ];
        $remember = $request->boolean('remember');

        if (! Auth::guard('clients')->attempt($credentials, $remember)) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Correo o contraseña incorrectos.',
                ], 401);
            }

            return back()
                ->withErrors(['gmail' => 'Correo o contraseña incorrectos.'])
                ->withInput();
        }

        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        if ($client->active === false) {
            Auth::guard('clients')->logout();
            $msg = 'En este momento se encuentra baneado, contactar con el administrador para más información.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }

            return back()->withErrors(['gmail' => $msg])->withInput();
        }

        if ($client->email_verified === false) {
            Auth::guard('clients')->logout();
            $this->verificationCodes->assignAndSend($client, ClientVerificationCodeIssuer::CONTEXT_LOGIN);
            $this->sessionState->setPendingRegistration($client);

            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'redirect' => route('clients.verify.form'),
                    'message' => 'Debes verificar tu correo antes de iniciar sesión.',
                ], 403);
            }

            return redirect()->route('clients.verify.form')
                ->withErrors(['gmail' => 'Debes verificar tu correo antes de iniciar sesión.']);
        }

        $this->establishClientSession->handle($client, $remember);

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'redirect' => route('clients.catalog'),
                'message' => 'Inicio de sesión exitoso.',
                'display_name' => $this->sessionState->welcomeDisplayName($client),
            ]);
        }

        return redirect()->route('clients.catalog')->with('client_success_modal', [
            'kind' => 'welcome',
            'authIcon' => 'user',
            'displayName' => $this->sessionState->welcomeDisplayName($client),
        ]);
    }
}
