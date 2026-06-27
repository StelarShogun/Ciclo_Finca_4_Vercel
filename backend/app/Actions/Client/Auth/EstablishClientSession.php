<?php

namespace App\Actions\Client\Auth;

use App\Models\Client;
use App\Services\Client\Auth\ClientAuthSessionState;
use App\Services\Client\Cart\CartManager;
use Illuminate\Support\Facades\Auth;

final class EstablishClientSession
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
        private CartManager $cartManager,
    ) {}

    public function handle(Client $client, bool $remember = false): void
    {
        Auth::guard('clients')->login($client, $remember);
        $this->sessionState->setAuthenticatedClientSession($client);
        $this->cartManager->mergeOnLogin((int) $client->user_id);
    }
}
