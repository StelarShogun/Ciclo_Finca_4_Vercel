<?php

namespace App\Services\Vercel;

use Illuminate\Support\Facades\Http;

final class QstashPublisher
{
    public function publish(string $path, array $body = [], ?int $delaySeconds = null): void
    {
        $token = (string) config('vercel.qstash_token', '');

        if ($token === '') {
            throw new \RuntimeException('QSTASH_TOKEN is required for Vercel background jobs.');
        }

        $url = rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
        $delay = $delaySeconds ?? (int) config('vercel.job_delay_seconds', 1);

        $response = Http::withToken($token)
            ->withHeaders(array_filter([
                'Content-Type' => 'application/json',
                'Upstash-Delay' => $delay > 0 ? $delay.'s' : null,
            ]))
            ->post(rtrim((string) config('vercel.qstash_base_url'), '/').'/publish/'.rawurlencode($url), $body);

        if (! $response->successful()) {
            throw new \RuntimeException('QStash publish failed: '.$response->body());
        }
    }
}
