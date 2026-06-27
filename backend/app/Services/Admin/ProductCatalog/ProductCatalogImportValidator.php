<?php

namespace App\Services\Admin\ProductCatalog;

use Illuminate\Support\Facades\Storage;

final class ProductCatalogImportValidator
{
    /**
     * @return array{valid: bool, message?: string}
     */
    public function validateStoredBlob(string $disk, string $path): array
    {
        if (! Storage::disk($disk)->exists($path)) {
            return [
                'valid' => false,
                'message' => 'El archivo subido a Blob no está disponible para importar.',
            ];
        }

        return ['valid' => true];
    }
}
