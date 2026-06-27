<?php

namespace App\Policies;

use App\Models\Client;
use App\Policies\Concerns\HandlesPolicyUsers;

final class UserProfilePolicy
{
    use HandlesPolicyUsers;

    public function view(mixed $user, Client $client): bool
    {
        return $this->isAdmin($user) || $this->ownsClientId($user, $client->user_id);
    }

    public function update(mixed $user, Client $client): bool
    {
        return $this->view($user, $client);
    }
}
