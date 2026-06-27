<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Notifications\BuildNotificationsHeartbeat;
use App\Actions\Client\Notifications\ListClientNotifications;
use App\Actions\Client\Notifications\MarkAllNotificationsAsRead;
use App\Actions\Client\Notifications\MarkNotificationAsRead;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

final class NotificationController extends Controller
{
    public function notificationsHeartbeat(BuildNotificationsHeartbeat $heartbeat)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        return response()->json($heartbeat->handle($client));
    }

    public function notifications(Request $request, ListClientNotifications $notifications)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        return Inertia::render('Client/Notifications/Index', $notifications->handle($client, $request));
    }

    public function markRead(string $notification, MarkNotificationAsRead $markNotificationAsRead)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        return response()->json([
            'success' => $markNotificationAsRead->handle($client, $notification),
        ]);
    }

    public function markAllRead(MarkAllNotificationsAsRead $markAllNotificationsAsRead)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        return response()->json([
            'success' => true,
            'updated' => $markAllNotificationsAsRead->handle($client),
        ]);
    }
}
