<?php

namespace App\Policies;

use App\Models\Product;
use App\Policies\Concerns\HandlesPolicyUsers;

final class ProductPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, Product $product): bool
    {
        return $this->isAdmin($user) || $product->isPurchasableByClient();
    }

    public function create(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(mixed $user, Product $product): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(mixed $user, Product $product): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(mixed $user, Product $product): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(mixed $user, Product $product): bool
    {
        return $this->isAdmin($user);
    }

    public function export(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function import(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function toggle(mixed $user, Product $product): bool
    {
        return $this->isAdmin($user);
    }
}
