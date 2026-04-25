<?php

namespace App\Mail;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Sale $sale,
        public readonly Carbon $expiresAt,
        public readonly string $clientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio: Tu pedido #' . $this->sale->sale_id . ' vence mañana',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-expiry-reminder',
        );
    }
}
