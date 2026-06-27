<?php

namespace App\Policies;

use App\Models\InventoryMovement;
use App\Policies\Concerns\HandlesPolicyUsers;

final class InventoryMovementPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, InventoryMovement $movement): bool
    {
        return $this->isAdmin($user);
    }

    public function create(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function export(mixed $user): bool
    {
        return $this->isAdmin($user);
    }
}
