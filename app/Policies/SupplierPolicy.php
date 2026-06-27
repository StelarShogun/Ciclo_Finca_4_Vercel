<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Policies\Concerns\HandlesPolicyUsers;

final class SupplierPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, Supplier $supplier): bool
    {
        return $this->isAdmin($user);
    }

    public function create(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(mixed $user, Supplier $supplier): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(mixed $user, Supplier $supplier): bool
    {
        return $this->isAdmin($user);
    }

    public function import(mixed $user): bool
    {
        return $this->isAdmin($user);
    }
}
