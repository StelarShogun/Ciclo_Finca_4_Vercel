<?php

namespace App\Services\Client\Auth;

use App\Mail\ClientRecoveryCodeMail;
use App\Mail\ClientVerificationCodeMail;
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
        $ttlMinutes = $this->ttlMinutesForContext($context);

        $client->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        try {
            $mailable = $context === self::CONTEXT_RECOVERY
                ? new ClientRecoveryCodeMail($client, $code)
                : new ClientVerificationCodeMail($client, $code, $context);

            Mail::to($client->gmail)->send($mailable);
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

    private function ttlMinutesForContext(string $context): int
    {
        return match ($context) {
            self::CONTEXT_RECOVERY => 15,
            self::CONTEXT_REGISTRATION, self::CONTEXT_LOGIN, self::CONTEXT_RESEND => 10,
            default => throw new \InvalidArgumentException("Unknown verification context: {$context}"),
        };
    }
}
