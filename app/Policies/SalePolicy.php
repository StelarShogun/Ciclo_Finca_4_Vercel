<?php

namespace App\Policies;

use App\Models\Sale;
use App\Policies\Concerns\HandlesPolicyUsers;

final class SalePolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, Sale $sale): bool
    {
        return $this->isAdmin($user) || $this->ownsClientId($user, $sale->client_id);
    }

    public function create(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(mixed $user, Sale $sale): bool
    {
        return $this->isAdmin($user);
    }

    public function cancel(mixed $user, Sale $sale): bool
    {
        return $this->isAdmin($user);
    }

    public function complete(mixed $user, Sale $sale): bool
    {
        return $this->isAdmin($user);
    }

    public function return(mixed $user, Sale $sale): bool
    {
        return $this->isAdmin($user);
    }

    public function markReady(mixed $user, Sale $sale): bool
    {
        return $this->isAdmin($user);
    }

    public function export(mixed $user): bool
    {
        return $this->isAdmin($user);
    }
}
