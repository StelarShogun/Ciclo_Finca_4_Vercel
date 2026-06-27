<?php

namespace App\Policies;

use App\Models\Brand;
use App\Policies\Concerns\HandlesPolicyUsers;

final class BrandPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, Brand $brand): bool
    {
        return $this->isAdmin($user);
    }

    public function create(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(mixed $user, Brand $brand): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(mixed $user, Brand $brand): bool
    {
        return $this->isAdmin($user);
    }
}
