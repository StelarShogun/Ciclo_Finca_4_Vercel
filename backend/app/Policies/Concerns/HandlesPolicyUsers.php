<?php

namespace App\Policies\Concerns;

use App\Models\AdminUser;
use App\Models\Client;

trait HandlesPolicyUsers
{
    protected function isAdmin(mixed $user): bool
    {
        return $user instanceof AdminUser;
    }

    protected function ownsClientId(mixed $user, mixed $clientId): bool
    {
        return $user instanceof Client && (int) $user->user_id === (int) $clientId;
    }
}
