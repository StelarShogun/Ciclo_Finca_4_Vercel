<?php

namespace App\Notifications;

use App\Models\Sale;
use App\Support\ClientPickupPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderReadyToPickupNotification extends Notification
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
        $invoicesUrl = $this->invoicesUrlAbsolute();

        return (new MailMessage)
            ->subject(
                'Su pedido '.($this->sale->invoice_number ?? '#'.$this->sale->sale_id).' está listo para recoger - Ciclo Finca 4'
            )
            ->view('emails.order-ready-to-pickup', [
                'sale' => $this->sale->loadMissing(['saleItems.product']),
                'clientName' => $clientName,
                'invoicesUrl' => $invoicesUrl,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $invoiceLabel = $this->sale->invoice_number ?? '#'.$this->sale->sale_id;

        return [
            'sale_id' => $this->sale->sale_id,
            'invoice_number' => $this->sale->invoice_number,
            'message' => sprintf(
                'Tu pedido %s ya está listo para recoger en tienda. %s',
                $invoiceLabel,
                ClientPickupPolicy::summaryLine(),
            ),
            'action_url' => $this->actionUrlRelative(),
            'action_label' => $this->actionLabel(),
        ];
    }

    private function actionUrlRelative(): string
    {
        $tab = $this->sale->status === 'completed' ? 'historial' : 'facturas';

        return route('clients.invoices', ['tab' => $tab], absolute: false);
    }

    private function actionLabel(): string
    {
        return $this->sale->status === 'completed'
            ? 'Ver en Historial de compras'
            : 'Ver en Facturas';
    }

    private function invoicesUrlAbsolute(): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $path = route('clients.invoices', ['tab' => 'facturas'], absolute: false);

        return str_starts_with($path, 'http') ? $path : $base.$path;
    }

    private function resolveClientName(object $notifiable): string
    {
        $name = trim(($notifiable->name ?? '').' '.($notifiable->first_surname ?? ''));

        return $name !== '' ? $name : 'Cliente';
    }
}
