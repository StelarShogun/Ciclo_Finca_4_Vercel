<?php

namespace App\Http\Requests\Concerns;

use App\Services\Api\PublicIdMapper;

/**
 * Acepta `product_id` como ID público (ULID del SPA) o interno (web viejo).
 * Antes de validar, el ULID se resuelve al autoincremental; si no existe,
 * se deja tal cual y la regla `exists` rechaza con 422.
 */
trait ResolvesPublicProductId
{
    protected function prepareForValidation(): void
    {
        $value = $this->input('product_id');
        if (is_string($value) && ! ctype_digit($value)) {
            $internal = app(PublicIdMapper::class)->internalId('product', $value);
            if ($internal !== null) {
                $this->merge(['product_id' => $internal]);
            }
        }
    }
}
