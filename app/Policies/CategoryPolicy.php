<?php

namespace App\Policies;

use App\Models\Category;
use App\Policies\Concerns\HandlesPolicyUsers;

final class CategoryPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }

    public function create(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(mixed $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(mixed $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }
}
