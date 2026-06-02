<?php

namespace App\Services\Client\Auth;

use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class ClientVerificationCodeIssuer
{
    public const CONTEXT_REGISTRATION = 'registration';

    public const CONTEXT_LOGIN = 'login';

    public const CONTEXT_RESEND = 'resend';

    public const CONTEXT_RECOVERY = 'recovery';

    /**
     * Assign a new code, persist expiry, and send email. Returns a user-facing warning when mail fails.
     */
    public function assignAndSend(Client $client, string $context): ?string
    {
        $code = $this->generateCode();
        [$ttlMinutes, $subject, $bodyTemplate] = $this->messageForContext($client, $context);
        $body = str_replace('{code}', $code, $bodyTemplate);

        $client->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        try {
            Mail::raw($body, function ($message) use ($client, $subject) {
                $message->to($client->gmail)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Mail send failed: client verification code', [
                'context' => $context,
                'client_id' => $client->user_id ?? null,
                'email' => $client->gmail ?? null,
                'exception' => $e,
            ]);

            return match ($context) {
                self::CONTEXT_REGISTRATION => 'No se pudo enviar el correo automáticamente. Por favor, usa la opción «Reenviar código» para intentarlo de nuevo.',
                self::CONTEXT_RESEND => 'No se pudo enviar el correo. Por favor, intenta reenviar el código nuevamente.',
                default => null,
            };
        }

        return null;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{0: int, 1: string, 2: string}
     */
    private function messageForContext(Client $client, string $context): array
    {
        return match ($context) {
            self::CONTEXT_REGISTRATION => [
                10,
                'Código de verificación - Ciclo Finca',
                "Hola {$client->name},\n\nTu código de verificación es: {code}\n\nExpira en 10 minutos.\n\nSi no creaste esta cuenta, ignora este correo.",
            ],
            self::CONTEXT_LOGIN => [
                10,
                'Código de verificación - Ciclo Finca',
                "Hola {$client->name},\n\nTu código de verificación es: {code}\n\nExpira en 10 minutos.",
            ],
            self::CONTEXT_RESEND => [
                10,
                'Nuevo código de verificación - Ciclo Finca',
                "Hola {$client->name},\n\nTu nuevo código de verificación es: {code}\n\nExpira en 10 minutos.",
            ],
            self::CONTEXT_RECOVERY => [
                15,
                'Código de recuperación de contraseña - Ciclo Finca 4',
                "Hola {$client->name},\n\nTu código de verificación para recuperar tu contraseña es: {code}\n\nExpira en 15 minutos.\n\nSi no solicitaste este cambio, ignora este correo.",
            ],
            default => throw new \InvalidArgumentException("Unknown verification context: {$context}"),
        };
    }
}
