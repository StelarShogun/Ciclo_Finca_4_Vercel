<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesPolicyUsers;

final class ReportPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(mixed $user): bool
    {
        return $this->isAdmin($user);
    }

    public function export(mixed $user): bool
    {
        return $this->isAdmin($user);
    }
}
