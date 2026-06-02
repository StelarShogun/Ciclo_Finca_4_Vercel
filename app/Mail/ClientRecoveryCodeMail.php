<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientRecoveryCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Client $client,
        public readonly string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de recuperación de contraseña - Ciclo Finca 4',
        );
    }

    public function content(): Content
    {
        $name = $this->client->name;
        $body = "Hola {$name},\n\nTu código de verificación para recuperar tu contraseña es: {$this->code}\n\nExpira en 15 minutos.\n\nSi no solicitaste este cambio, ignora este correo.";

        return new Content(
            text: 'emails.client-plain-text',
            with: ['body' => $body],
        );
    }
}
