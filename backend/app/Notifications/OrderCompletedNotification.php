<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Sale $sale,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $clientName = $this->resolveClientName($notifiable);
        $historyUrl = $this->historyUrlAbsolute();

        return (new MailMessage)
            ->subject(
                'Su pedido '.($this->sale->invoice_number ?? '#'.$this->sale->sale_id).' fue confirmado - Ciclo Finca 4'
            )
            ->view('emails.order-completed', [
                'sale' => $this->sale->loadMissing(['saleItems.product']),
                'clientName' => $clientName,
                'historyUrl' => $historyUrl,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $invoiceLabel = $this->sale->invoice_number ?? '#'.$this->sale->sale_id;

        return [
            'sale_id' => $this->sale->sale_id,
            'invoice_number' => $this->sale->invoice_number,
            'message' => sprintf(
                'Tu pedido %s fue confirmado. Ya está disponible en Historial de compras.',
                $invoiceLabel
            ),
            'action_url' => $this->historyUrlRelative(),
            'action_label' => 'Ver en Historial de compras',
        ];
    }

    private function historyUrlRelative(): string
    {
        return route('clients.invoices', ['tab' => 'historial'], absolute: false);
    }

    private function historyUrlAbsolute(): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $path = $this->historyUrlRelative();

        return str_starts_with($path, 'http') ? $path : $base.$path;
    }

    private function resolveClientName(object $notifiable): string
    {
        $name = trim(($notifiable->name ?? '').' '.($notifiable->first_surname ?? ''));

        return $name !== '' ? $name : 'Cliente';
    }
}
