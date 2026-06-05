<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Sale;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderReadyToPickupNotification;
use App\Services\Client\Cart\CartManager;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly CartManager $cartManager,
    ) {}

    public function notificationsHeartbeat()
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
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

        $toasts = $client->unreadNotifications()
            ->whereIn('type', array_keys($typeMap))
            ->latest()
            ->limit(5)
            ->get()
            ->map(static function ($notification) use ($typeMap) {
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
            ->values();

        return response()->json([
            'unread_count' => $client->unreadNotifications()->count(),
            'invoice_count' => Sale::countActiveClientInvoices($clientId),
            'unseen_history' => Sale::countUnseenInClientHistory($clientId),
            'revision' => Sale::clientInvoicesRevision($clientId),
            'toasts' => $toasts,
        ]);
    }

    public function notifications(Request $request)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $client->unreadNotifications->markAsRead();

        $cartCount = $this->cartManager->totalItemCount();

        $notifications = $client->notifications()
            ->latest()
            ->paginate(AdminPerPage::resolve($request->input('per_page', 10)))
            ->withQueryString();

        $rows = collect($notifications->items())->map(function ($notification) {
            $data = is_array($notification->data) ? $notification->data : [];

            return [
                'id' => (string) $notification->id,
                'createdAtLabel' => optional($notification->created_at)->format('d/m/Y H:i') ?? '',
                'message' => (string) ($data['message'] ?? 'Notificación del sistema'),
                'actionUrl' => $data['action_url'] ?? null,
                'actionLabel' => $data['action_label'] ?? 'Abrir enlace',
            ];
        })->values()->all();

        return Inertia::render('Client/Notifications/Index', [
            'notifications' => $rows,
            'pagination' => ListPaginationPayload::from($notifications),
            'cartCount' => $cartCount,
        ]);
    }
}
