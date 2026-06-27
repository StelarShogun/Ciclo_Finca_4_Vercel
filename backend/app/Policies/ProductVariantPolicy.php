<?php

namespace App\Policies;

use App\Models\ProductVariant;
use App\Policies\Concerns\HandlesPolicyUsers;

final class ProductVariantPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, ProductVariant $variant): bool
    {
        return $this->isAdmin($user);
    }

    public function create(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(mixed $user, ProductVariant $variant): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(mixed $user, ProductVariant $variant): bool
    {
        return $this->isAdmin($user);
    }
}
