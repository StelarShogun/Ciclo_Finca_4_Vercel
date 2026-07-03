<?php

namespace App\Services\Vercel;

use Illuminate\Support\Facades\Http;

final class QstashPublisher
{
    /**
     * @param  array<string, string>  $forwardHeaders  Headers que QStash debe
     *                                                 reenviar al destino (se envían con el prefijo Upstash-Forward-).
     */
    public function publish(string $path, array $body = [], ?int $delaySeconds = null, array $forwardHeaders = []): void
    {
        $token = (string) config('vercel.qstash_token', '');

        if ($token === '') {
            throw new \RuntimeException('QSTASH_TOKEN is required for Vercel background jobs.');
        }

        $url = $this->destinationUrl($path);
        $delay = $delaySeconds ?? (int) config('vercel.job_delay_seconds', 1);

        $forwarded = [];
        foreach ($forwardHeaders as $name => $value) {
            $forwarded['Upstash-Forward-'.$name] = (string) $value;
        }

        // QStash espera la URL de destino anexada SIN codificar al endpoint de
        // publicación: /v2/publish/https://host/path. Si se envía percent-encoded,
        // QStash la lee como esquema inválido ("https%3A...").
        $response = Http::withToken($token)
            ->withHeaders(array_filter([
                'Content-Type' => 'application/json',
                'Upstash-Delay' => $delay > 0 ? $delay.'s' : null,
            ]) + $forwarded)
            ->post($this->baseUrl().'/publish/'.$url, $body);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'QStash publish failed (HTTP '.$response->status().'): '.$response->body(),
            );
        }
    }

    /**
     * Construye la URL pública de destino, garantizando esquema https://
     * (QStash rechaza destinos sin esquema válido).
     */
    private function destinationUrl(string $path): string
    {
        $appUrl = trim((string) config('app.url'));

        if ($appUrl === '' || ! preg_match('#^https?://#i', $appUrl)) {
            $appUrl = 'https://'.ltrim($appUrl, '/');
        }

        return rtrim($appUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * Normaliza la base de QStash para que siempre apunte a la API v2,
     * aceptando tanto QSTASH_URL (https://qstash.upstash.io) como una base
     * que ya incluya /v2.
     */
    private function baseUrl(): string
    {
        $base = rtrim((string) config('vercel.qstash_base_url', 'https://qstash.upstash.io/v2'), '/');

        if (! str_ends_with($base, '/v2')) {
            $base .= '/v2';
        }

        return $base;
    }
}
