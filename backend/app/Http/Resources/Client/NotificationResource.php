<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/** @mixin DatabaseNotification */
final class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : [];

        return [
            'id' => (string) $this->id,
            'createdAtLabel' => optional($this->created_at)->format('d/m/Y H:i') ?? '',
            'message' => (string) ($data['message'] ?? 'Notificación del sistema'),
            'actionUrl' => $data['action_url'] ?? null,
            'actionLabel' => $data['action_label'] ?? 'Abrir enlace',
        ];
    }
}
