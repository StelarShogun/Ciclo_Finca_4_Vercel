<?php

namespace App\Policies;

use App\Models\Order;

final class SupplierOrderPolicy extends OrderPolicy
{
    public function closePartial(mixed $user, Order $order): bool
    {
        return $this->isAdmin($user);
    }
}
