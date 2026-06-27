<?php

namespace App\Services\Client\Auth;

use App\Models\Client;
use App\Services\Client\Cart\CartManager;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\Client\Auth\GoogleProfileNameParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

final class GoogleOAuthService
{
    private const STATE_COOKIE = 'google_oauth_state';

    private const STATE_TTL_MINUTES = 10;

    public function __construct(
        private ClientAuthSessionState $sessionState,
        private CartManager $cartManager,
        private GoogleProfileNameParser $nameParser,
    ) {}

    public function redirectToProvider(): RedirectResponse
    {
        $googleConfig = config('services.google');

        if (empty($googleConfig['client_id']) || empty($googleConfig['redirect'])) {
            return redirect()->route('clients.home')->with(
                'error',
                'Falta configurar GOOGLE_CLIENT_ID o GOOGLE_REDIRECT_URI en el entorno.'
            );
        }

        $state = $this->issueState();

        $query = http_build_query([
            'client_id' => $googleConfig['client_id'],
            'redirect_uri' => $googleConfig['redirect'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()
            ->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query)
            ->withCookie($this->stateCookie($state));
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        try {
            if ($request->filled('error')) {
                return redirect()->route('clients.home')->with(
                    'error',
                    'Google rechazó la autenticación: '.$request->string('error')
                );
            }

            if (! $this->consumeState($request)) {
                Log::warning('oauth_state_mismatch', [
                    'request_present' => $request->filled('state'),
                    'cookie_present' => $request->hasCookie(self::STATE_COOKIE),
                    'session_driver' => config('session.driver'),
                    'cache_store' => config('cache.default'),
                ]);

                return redirect()->route('clients.home')->with('error', 'Sesión OAuth inválida. Inténtalo de nuevo.');
            }

            $authCode = (string) $request->query('code', '');
            if ($authCode === '') {
                return redirect()->route('clients.home')->with('error', 'No se recibió el código OAuth de Google.');
            }

            $googleUser = $this->fetchGoogleUserProfile($authCode);
            $email = strtolower((string) data_get($googleUser, 'email', ''));

            if ($email === '') {
                throw new \RuntimeException('Google no devolvió un correo electrónico.');
            }

            $client = $this->resolveOrCreateClient($googleUser, $email);

            if ($client->active === false) {
                $msg = 'En este momento se encuentra baneado, contactar con el administrador para más información.';

                return redirect()->route('clients.home')->with('error', $msg);
            }

            Auth::guard('clients')->login($client);
            $request->session()->regenerate();
            $this->sessionState->setAuthenticatedClientSession($client);
            $this->cartManager->mergeOnLogin((int) $client->user_id);

            return redirect()->route('clients.catalog')->with('client_success_modal', [
                'kind' => 'welcome',
                'authIcon' => 'google',
                'displayName' => $this->sessionState->welcomeDisplayName($client),
            ]);
        } catch (\Throwable $e) {
            Log::error('client_google_oauth_failed', SensitiveDataMasker::exceptionContext($e));

            return redirect()
                ->route('clients.home')
                ->with('error', 'No fue posible iniciar sesión con Google. Inténtalo nuevamente.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchGoogleUserProfile(string $authCode): array
    {
        $googleConfig = config('services.google');
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $authCode,
            'client_id' => $googleConfig['client_id'] ?? null,
            'client_secret' => $googleConfig['client_secret'] ?? null,
            'redirect_uri' => $googleConfig['redirect'] ?? null,
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResponse->successful()) {
            throw new \RuntimeException('No se pudo obtener el access token de Google.');
        }

        $accessToken = (string) data_get($tokenResponse->json(), 'access_token', '');
        if ($accessToken === '') {
            throw new \RuntimeException('Google no devolvió access_token.');
        }

        $profileResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
        if (! $profileResponse->successful()) {
            throw new \RuntimeException('No se pudo obtener el perfil de Google.');
        }

        return $profileResponse->json();
    }

    /**
     * @param  array<string, mixed>  $googleUser
     */
    private function resolveOrCreateClient(array $googleUser, string $email): Client
    {
        $client = Client::where('gmail', $email)->first();

        if ($client) {
            if (Schema::hasColumn((new Client)->getTable(), 'email_verified')) {
                $client->update(['email_verified' => true]);
            }

            $update = [];
            $providerId = (string) data_get($googleUser, 'sub', '');
            if ($providerId !== '' && Schema::hasColumn((new Client)->getTable(), 'provider_id')) {
                $update['provider_id'] = $providerId;
            }
            $picture = (string) data_get($googleUser, 'picture', '');
            if ($picture !== '' && Schema::hasColumn((new Client)->getTable(), 'avatar_url')) {
                $update['avatar_url'] = $picture;
            }
            if (Schema::hasColumn((new Client)->getTable(), 'provider')) {
                $update['provider'] = 'google';
            }
            if ($update !== []) {
                $client->update($update);
            }

            return $client;
        }

        $parsedNames = $this->nameParser->parse($googleUser);
        $data = [
            'name' => $parsedNames['name'],
            'first_surname' => $parsedNames['first_surname'],
            'second_surname' => $parsedNames['second_surname'],
            'gmail' => $email,
            'password' => bcrypt(Str::random(32)),
            'provider' => 'google',
            'provider_id' => (string) data_get($googleUser, 'sub', ''),
        ];
        if (Schema::hasColumn((new Client)->getTable(), 'email_verified')) {
            $data['email_verified'] = true;
        }
        $picture = (string) data_get($googleUser, 'picture', '');
        if ($picture !== '' && Schema::hasColumn((new Client)->getTable(), 'avatar_url')) {
            $data['avatar_url'] = $picture;
        }

        return Client::create($data);
    }

    private function issueState(): string
    {
        $state = Str::random(40);
        Cache::put(
            'google_oauth_state:'.$state,
            1,
            now()->addMinutes(self::STATE_TTL_MINUTES)
        );

        return $state;
    }

    private function stateCookie(string $state): SymfonyCookie
    {
        return cookie(
            self::STATE_COOKIE,
            $state,
            self::STATE_TTL_MINUTES,
            config('session.path', '/'),
            config('session.domain'),
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }

    private function consumeState(Request $request): bool
    {
        $stateFromRequest = (string) $request->query('state', '');
        if ($stateFromRequest === '') {
            return false;
        }

        $stateFromCookie = (string) $request->cookie(self::STATE_COOKIE, '');
        $hadCacheEntry = Cache::pull('google_oauth_state:'.$stateFromRequest) !== null;

        Cookie::queue(Cookie::forget(
            self::STATE_COOKIE,
            config('session.path', '/'),
            config('session.domain')
        ));

        if ($stateFromCookie !== '' && hash_equals($stateFromCookie, $stateFromRequest)) {
            return true;
        }

        return $hadCacheEntry;
    }
}
