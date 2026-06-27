<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesPolicyUsers;
use Illuminate\Notifications\DatabaseNotification;

final class NotificationPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return $user !== null;
    }

    public function view(mixed $user, DatabaseNotification $notification): bool
    {
        return $this->isAdmin($user) || $this->ownsClientId($user, $notification->notifiable_id);
    }

    public function markRead(mixed $user, DatabaseNotification $notification): bool
    {
        return $this->view($user, $notification);
    }

    public function markAllRead(mixed $user): bool
    {
        return $user !== null;
    }
}
