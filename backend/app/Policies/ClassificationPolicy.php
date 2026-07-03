<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesPolicyUsers;

final class ClassificationPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, mixed $classification = null): bool
    {
        return $this->isAdmin($user);
    }

    public function create(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(mixed $user, mixed $classification = null): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(mixed $user, mixed $classification = null): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(mixed $user, mixed $classification = null): bool
    {
        return $this->isAdmin($user);
    }
}
