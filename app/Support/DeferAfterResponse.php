<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Run slow I/O (mail, external APIs) after the HTTP response is flushed to the client.
 */
final class DeferAfterResponse
{
    public static function run(callable $callback): void
    {
        dispatch(static function () use ($callback): void {
            try {
                $callback();
            } catch (\Throwable $e) {
                Log::warning('Deferred after-response task failed.', [
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }
}
