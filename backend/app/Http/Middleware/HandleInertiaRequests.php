<?php

namespace App\Http\Middleware;

use App\Services\Client\Cart\CartManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'client' => fn () => $this->clientPayload(),
                'admin' => fn () => $this->adminPayload(),
            ],
            'cartCount' => fn () => app(CartManager::class)->totalItemCount(),
            'csrfToken' => fn () => csrf_token(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                // El admin usa 'status' para mensajes de éxito (redirect()->with('status', …)).
                'status' => fn () => $request->session()->get('status'),
                'error' => fn () => $request->session()->get('error'),
                'clientSuccessModal' => fn () => $request->session()->get('client_success_modal'),
            ],
            'theme' => fn () => $request->cookie('cf4-theme'),
            'favorites' => fn () => Auth::guard('clients')->check()
                ? [
                    'indexUrl' => route('clients.favorites.index'),
                    'toggleUrl' => route('clients.favorites.toggle'),
                ]
                : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function clientPayload(): ?array
    {
        $client = Auth::guard('clients')->user();
        if (! $client) {
            return null;
        }

        return [
            'id' => (int) $client->user_id,
            'name' => $client->name,
            'first_surname' => $client->first_surname,
            'second_surname' => $client->second_surname,
            'gmail' => $client->gmail,
            'email_verified' => (bool) ($client->email_verified ?? false),
            'provider' => $client->provider ?? 'local',
            'avatarUrl' => $client->avatar_url ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function adminPayload(): ?array
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            return null;
        }

        return [
            'id' => (int) $admin->user_id,
            'name' => $admin->name,
            'first_surname' => $admin->first_surname,
            'second_surname' => $admin->second_surname,
            'gmail' => $admin->gmail,
        ];
    }
}
