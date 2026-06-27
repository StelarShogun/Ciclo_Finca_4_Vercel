<?php

namespace App\Services\Admin\Audit;

final class AuditPayloadSanitizer
{
    private const SENSITIVE_KEYS = [
        'address',
        'authorization',
        'buyer_email',
        'buyer_name',
        'code',
        'current_password',
        'email',
        'gmail',
        'new_password',
        'password',
        'password_confirmation',
        'payment_reference',
        'phone',
        'remember_token',
        'secret',
        'token',
    ];

    public function sanitize(array $payload): array
    {
        return $this->sanitizeArray($payload);
    }

    private function sanitizeArray(array $payload): array
    {
        $safe = [];

        foreach ($payload as $key => $value) {
            $safe[$key] = $this->isSensitive((string) $key)
                ? $this->mask($value)
                : $this->sanitizeValue($value);
        }

        return $safe;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }

        if (is_string($value) && mb_strlen($value) > 500) {
            return mb_substr($value, 0, 500).'...';
        }

        return $value;
    }

    private function isSensitive(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function mask(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return '[masked]';
    }
}
