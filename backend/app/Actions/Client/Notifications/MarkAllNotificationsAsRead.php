<?php

namespace App\Actions\Client\Notifications;

use App\Models\Client;

final class MarkAllNotificationsAsRead
{
    public function handle(Client $client): int
    {
        return $client->unreadNotifications()->update(['read_at' => now()]);
    }
}
