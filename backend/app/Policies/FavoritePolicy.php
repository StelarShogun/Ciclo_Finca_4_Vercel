<?php

namespace App\Policies;

use App\Models\FavoriteProduct;
use App\Policies\Concerns\HandlesPolicyUsers;

final class FavoritePolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $user !== null;
    }

    public function view(mixed $user, FavoriteProduct $favorite): bool
    {
        return $this->isAdmin($user) || $this->ownsClientId($user, $favorite->user_id);
    }

    public function create(mixed $user): bool
    {
        return $user !== null && ! $this->isAdmin($user);
    }

    public function delete(mixed $user, FavoriteProduct $favorite): bool
    {
        return $this->view($user, $favorite);
    }

    public function toggle(mixed $user): bool
    {
        return $user !== null && ! $this->isAdmin($user);
    }
}
