<?php

namespace App\Policies;

use App\Models\Sale;
use App\Policies\Concerns\HandlesPolicyUsers;

final class InvoicePolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user) || $user !== null;
    }

    public function view(mixed $user, Sale $sale): bool
    {
        return $this->isAdmin($user) || $this->ownsClientId($user, $sale->client_id);
    }

    public function export(mixed $user, Sale $sale): bool
    {
        return $this->view($user, $sale);
    }
}
