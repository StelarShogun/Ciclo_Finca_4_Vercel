<?php

namespace App\Actions\Client\Notifications;

use App\Models\Client;
use App\Models\Sale;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderReadyToPickupNotification;

final class BuildNotificationsHeartbeat
{
    /**
     * @return array<string,mixed>
     */
    public function handle(Client $client): array
    {
        $clientId = (int) $client->user_id;
        $typeMap = [
            OrderReadyToPickupNotification::class => [
                'kind' => 'ready_to_pickup',
                'title' => '¡Listo para recoger!',
            ],
            OrderCompletedNotification::class => [
                'kind' => 'completed',
                'title' => '¡Pedido confirmado!',
            ],
            OrderCancelledNotification::class => [
                'kind' => 'cancelled',
                'title' => 'Pedido cancelado',
            ],
        ];

        return [
            'unread_count' => $client->unreadNotifications()->count(),
            'invoice_count' => Sale::countActiveClientInvoices($clientId),
            'unseen_history' => Sale::countUnseenInClientHistory($clientId),
            'revision' => Sale::clientInvoicesRevision($clientId),
            'toasts' => $client->unreadNotifications()
                ->whereIn('type', array_keys($typeMap))
                ->latest()
                ->limit(5)
                ->get()
                ->map(static function ($notification) use ($typeMap): array {
                    $data = is_array($notification->data) ? $notification->data : [];
                    $meta = $typeMap[$notification->type] ?? ['kind' => 'info', 'title' => 'Notificación'];

                    return [
                        'id' => (string) $notification->id,
                        'kind' => $meta['kind'],
                        'title' => $meta['title'],
                        'message' => (string) ($data['message'] ?? ''),
                        'action_url' => (string) ($data['action_url'] ?? route('clients.invoices', [], false)),
                        'action_label' => (string) ($data['action_label'] ?? 'Ver facturas'),
                    ];
                })
                ->values(),
        ];
    }
}
