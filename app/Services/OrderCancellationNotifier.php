<?php

namespace App\Services;

use App\Models\OrderNotificationLog;
use App\Models\Sale;
use App\Notifications\OrderCancelledNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class OrderCancellationNotifier
{
    public function notify(Sale $sale, string $reason, Carbon $cancelledAt): void
    {
        $sale->loadMissing('client');

        $client = $sale->client;
        $email = $client?->gmail ?? $sale->buyer_email;
        if (! $email) {
            $this->logChannel($sale, $client?->user_id, 'mail', 'skipped', $reason, $cancelledAt, null, 'Missing recipient email');
            Log::warning('Order cancellation notification skipped: missing recipient email.', [
                'sale_id' => $sale->sale_id,
                'reason' => $reason,
            ]);

            return;
        }

        $notification = new OrderCancelledNotification($sale, $reason, $cancelledAt);

        if ($client !== null) {
            try {
                $client->notify($notification);
                $now = now();
                $this->logChannel($sale, $client->user_id, 'mail', 'sent', $reason, $cancelledAt, $now);
                $this->logChannel($sale, $client->user_id, 'database', 'sent', $reason, $cancelledAt, $now);
            } catch (\Throwable $e) {
                $this->logChannel($sale, $client->user_id, 'mail', 'failed', $reason, $cancelledAt, null, $e->getMessage());
                $this->logChannel($sale, $client->user_id, 'database', 'failed', $reason, $cancelledAt, null, $e->getMessage());
                throw $e;
            }

            return;
        }

        try {
            Notification::route('mail', $email)->notify($notification);
            $this->logChannel($sale, null, 'mail', 'sent', $reason, $cancelledAt, now());
        } catch (\Throwable $e) {
            $this->logChannel($sale, null, 'mail', 'failed', $reason, $cancelledAt, null, $e->getMessage());
            throw $e;
        }
    }

    private function logChannel(
        Sale $sale,
        ?int $clientId,
        string $channel,
        string $status,
        string $reason,
        Carbon $cancelledAt,
        ?Carbon $sentAt = null,
        ?string $errorMessage = null
    ): void {
        OrderNotificationLog::create([
            'sale_id' => $sale->sale_id,
            'client_id' => $clientId,
            'channel' => $channel,
            'status' => $status,
            'reason' => $reason,
            'cancelled_at' => $cancelledAt,
            'sent_at' => $sentAt,
            'error_message' => $errorMessage,
        ]);
    }
}
