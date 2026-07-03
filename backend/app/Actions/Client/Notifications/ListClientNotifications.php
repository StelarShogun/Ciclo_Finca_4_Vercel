<?php

namespace App\Actions\Client\Notifications;

use App\Http\Resources\Client\NotificationResource;
use App\Models\Client;
use App\Services\Client\Cart\CartManager;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;

final class ListClientNotifications
{
    public function __construct(
        private CartManager $cartManager,
        private MarkAllNotificationsAsRead $markAllNotificationsAsRead,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function handle(Client $client, Request $request): array
    {
        $this->markAllNotificationsAsRead->handle($client);

        $notifications = $client->notifications()
            ->latest()
            ->paginate(AdminPerPage::resolve($request->input('per_page', 10)))
            ->withQueryString();

        return [
            'notifications' => NotificationResource::collection(collect($notifications->items()))->resolve($request),
            'pagination' => ListPaginationPayload::from($notifications),
            'cartCount' => $this->cartManager->totalItemCount(),
        ];
    }
}
