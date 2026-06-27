<?php

namespace App\Policies;

use App\Models\Order;
use App\Policies\Concerns\HandlesPolicyUsers;

class OrderPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user, Order $order): bool
    {
        return $this->isAdmin($user);
    }

    public function create(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(mixed $user, Order $order): bool
    {
        return $this->isAdmin($user);
    }

    public function cancel(mixed $user, Order $order): bool
    {
        return $this->isAdmin($user);
    }

    public function receive(mixed $user, Order $order): bool
    {
        return $this->isAdmin($user);
    }

    public function complete(mixed $user, Order $order): bool
    {
        return $this->isAdmin($user);
    }

    public function export(mixed $user): bool
    {
        return $this->isAdmin($user);
    }
}
