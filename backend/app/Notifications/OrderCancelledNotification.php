<?php

namespace App\Notifications;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Sale $sale,
        public readonly string $reason,
        public readonly Carbon $cancelledAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $clientName = $this->resolveClientName($notifiable);

        return (new MailMessage)
            ->subject('Pedido cancelado: #'.$this->sale->sale_id.' - Ciclo Finca 4')
            ->view('emails.order-cancelled-notification', [
                'sale' => $this->sale,
                'reason' => $this->reason,
                'cancelledAt' => $this->cancelledAt,
                'clientName' => $clientName,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'sale_id' => $this->sale->sale_id,
            'reason' => $this->reason,
            'cancelled_at' => $this->cancelledAt->toIso8601String(),
            'message' => sprintf(
                'Tu pedido %s fue cancelado. Motivo: %s.',
                $this->sale->invoice_number ?? '#'.$this->sale->sale_id,
                $this->reason
            ),
            'action_url' => route('clients.invoices', ['tab' => 'canceladas'], absolute: false),
            'action_label' => 'Ver en Canceladas',
        ];
    }

    private function resolveClientName(object $notifiable): string
    {
        $name = trim(($notifiable->name ?? '').' '.($notifiable->first_surname ?? ''));

        return $name !== '' ? $name : 'Cliente';
    }
}
