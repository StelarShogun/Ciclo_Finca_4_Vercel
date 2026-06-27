<?php

namespace App\Actions\Client\Notifications;

use App\Models\Client;

final class MarkNotificationAsRead
{
    public function handle(Client $client, string $notificationId): bool
    {
        return $client->unreadNotifications()
            ->whereKey($notificationId)
            ->update(['read_at' => now()]) > 0;
    }
}
