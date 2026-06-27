<?php

namespace App\Services\Shared\Security;

final class SensitiveDataMasker
{
    /**
     * @return array<string, mixed>
     */
    public static function exceptionContext(\Throwable $exception, array $context = []): array
    {
        return $context + [
            'exception' => $exception::class,
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message_hash' => hash('sha256', $exception->getMessage()),
        ];
    }
}
