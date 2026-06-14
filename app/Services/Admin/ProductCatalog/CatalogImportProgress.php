<?php

namespace App\Services\Admin\ProductCatalog;

use Illuminate\Support\Facades\Cache;

/**
 * Estado de progreso de una importación de catálogo, compartido entre la request
 * web (polling) y el job de cola que procesa el archivo. Usa el cache (file) para
 * que el avance sea visible en vivo entre procesos.
 */
final class CatalogImportProgress
{
    private const TTL = 3600; // 1h

    private const PREFIX = 'catalog_import:';

    private const ACTIVE_PREFIX = 'catalog_import:active:';

    public static function key(string $importId): string
    {
        return self::PREFIX.$importId;
    }

    public static function activeKey(int $adminId): string
    {
        return self::ACTIVE_PREFIX.$adminId;
    }

    /**
     * Marca una importación recién encolada y la fija como activa para el admin.
     *
     * @return array<string, mixed>
     */
    public static function queued(string $importId, int $adminId, string $filename): array
    {
        $payload = [
            'importId' => $importId,
            'status' => 'queued',
            'filename' => $filename,
            'total' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'level' => null,
            'message' => 'En cola, iniciando…',
            'startedAt' => now()->toIso8601String(),
            'updatedAt' => now()->toIso8601String(),
        ];

        Cache::put(self::key($importId), $payload, self::TTL);
        Cache::put(self::activeKey($adminId), $importId, self::TTL);

        return $payload;
    }

    /**
     * Aplica un parche al estado de progreso existente.
     *
     * @param  array<string, mixed>  $patch
     * @return array<string, mixed>
     */
    public static function put(string $importId, array $patch): array
    {
        $current = Cache::get(self::key($importId));
        $current = is_array($current) ? $current : [];
        $payload = array_merge($current, $patch, ['updatedAt' => now()->toIso8601String()]);
        Cache::put(self::key($importId), $payload, self::TTL);

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $importId): ?array
    {
        $value = Cache::get(self::key($importId));

        return is_array($value) ? $value : null;
    }

    public static function activeFor(int $adminId): ?string
    {
        $value = Cache::get(self::activeKey($adminId));

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function clearActive(int $adminId): void
    {
        Cache::forget(self::activeKey($adminId));
    }
}
