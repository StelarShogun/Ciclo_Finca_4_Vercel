<?php

namespace App\Policies;

use App\Models\Client;
use App\Policies\Concerns\HandlesPolicyUsers;

final class ClientPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, Client $client): bool
    {
        return $this->isAdmin($user) || $this->ownsClientId($user, $client->user_id);
    }

    public function update(mixed $user, Client $client): bool
    {
        return $this->isAdmin($user) || $this->ownsClientId($user, $client->user_id);
    }

    public function delete(mixed $user, Client $client): bool
    {
        return $this->isAdmin($user);
    }

    public function ban(mixed $user, Client $client): bool
    {
        return $this->isAdmin($user);
    }

    public function unban(mixed $user, Client $client): bool
    {
        return $this->isAdmin($user);
    }
}
