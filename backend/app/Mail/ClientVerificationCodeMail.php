<?php

namespace App\Mail;

use App\Models\Client;
use App\Services\Client\Auth\ClientVerificationCodeIssuer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Client $client,
        public readonly string $code,
        public readonly string $context,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectForContext(),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.client-plain-text',
            with: ['body' => $this->plainBody()],
        );
    }

    private function subjectForContext(): string
    {
        return match ($this->context) {
            ClientVerificationCodeIssuer::CONTEXT_RESEND => 'Nuevo código de verificación - Ciclo Finca',
            default => 'Código de verificación - Ciclo Finca',
        };
    }

    private function plainBody(): string
    {
        $name = $this->client->name;

        return match ($this->context) {
            ClientVerificationCodeIssuer::CONTEXT_REGISTRATION => "Hola {$name},\n\nTu código de verificación es: {$this->code}\n\nExpira en 10 minutos.\n\nSi no creaste esta cuenta, ignora este correo.",
            ClientVerificationCodeIssuer::CONTEXT_LOGIN => "Hola {$name},\n\nTu código de verificación es: {$this->code}\n\nExpira en 10 minutos.",
            ClientVerificationCodeIssuer::CONTEXT_RESEND => "Hola {$name},\n\nTu nuevo código de verificación es: {$this->code}\n\nExpira en 10 minutos.",
            default => throw new \InvalidArgumentException("Unknown verification context: {$this->context}"),
        };
    }
}
