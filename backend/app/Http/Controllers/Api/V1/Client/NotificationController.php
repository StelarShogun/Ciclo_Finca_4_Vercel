<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Notifications\BuildNotificationsHeartbeat;
use App\Actions\Client\Notifications\ListClientNotifications;
use App\Actions\Client\Notifications\MarkAllNotificationsAsRead;
use App\Actions\Client\Notifications\MarkNotificationAsRead;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Notificaciones del cliente para el SPA Next. Reusa las Actions; marcar leídas
 * solo afecta las notificaciones del propio cliente.
 */
final class NotificationController extends Controller
{
    public function index(Request $request, ListClientNotifications $notifications): JsonResponse
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        return response()->json(['data' => $notifications->handle($client, $request)]);
    }

    public function heartbeat(BuildNotificationsHeartbeat $heartbeat): JsonResponse
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        return response()->json($heartbeat->handle($client));
    }

    public function markRead(string $notification, MarkNotificationAsRead $action): JsonResponse
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        return response()->json(['success' => $action->handle($client, $notification)]);
    }

    public function markAllRead(MarkAllNotificationsAsRead $action): JsonResponse
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        return response()->json(['success' => true, 'updated' => $action->handle($client)]);
    }
}
