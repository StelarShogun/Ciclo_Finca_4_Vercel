<?php

namespace App\Notifications;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductReviewReminderNotification extends Notification
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
        $productPhrase = $this->productPhrase();
        $historyUrl = $this->historyUrlAbsolute();

        return (new MailMessage)
            ->subject('Reseña de productos comprados - Ciclo Finca 4')
            ->line("Estimado {$clientName},")
            ->line(' ')
            ->line("Favor reseñar {$productPhrase}.")
            ->line('Para esto, acceda a Facturas > Historial de compras:')
            ->line($historyUrl)
            ->line(' ')
            ->line('Gracias por comprar en Ciclo Finca 4.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'sale_id' => $this->sale->sale_id,
            'message' => sprintf(
                'Te invitamos a reseñar %s. Podés hacerlo desde Facturas > Historial de compras.',
                $this->productPhrase()
            ),
            // Relative path so in-app links follow the current host/port (Docker, artisan serve, etc.).
            'action_url' => $this->historyUrlRelative(),
            'action_label' => 'Ir al historial de compras',
        ];
    }

    private function productPhrase(): string
    {
        $productCount = SaleItem::query()
            ->where('sale_id', $this->sale->sale_id)
            ->distinct('product_id')
            ->count('product_id');

        return $productCount === 1 ? 'el producto comprado' : 'los productos comprados';
    }

    private function historyUrlRelative(): string
    {
        return route('clients.invoices', ['tab' => 'historial'], absolute: false);
    }

    /** Absolute URL for transactional mail clients (uses FRONTEND_URL / APP_URL). */
    private function historyUrlAbsolute(): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $path = $this->historyUrlRelative();

        return str_starts_with($path, 'http') ? $path : $base.$path;
    }

    private function resolveClientName(object $notifiable): string
    {
        $name = trim(($notifiable->name ?? '').' '.($notifiable->first_surname ?? ''));

        return $name !== '' ? $name : 'cliente';
    }
}
