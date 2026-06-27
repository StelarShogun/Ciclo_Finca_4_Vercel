<?php

namespace App\Services\Admin\ProductCatalog;

use App\Http\Requests\Admin\Products\ImportCatalogRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class ProductCatalogImportStorage
{
    public function __construct(private ProductCatalogImportValidator $validator) {}

    /**
     * @return array{importId: string, disk: string, storedPath: string, originalName: string, error?: string}
     */
    public function store(ImportCatalogRequest $request): array
    {
        $importId = (string) Str::uuid();
        $disk = config('vercel.enabled') ? (string) config('vercel.import_disk') : 'local';

        if ($request->filled('blob_path') && config('vercel.enabled')) {
            $blobPath = (string) $request->input('blob_path');
            $storedPath = (string) ($request->input('blob_url') ?: $blobPath);
            $originalName = (string) ($request->input('original_name') ?: basename($blobPath));
            $validation = $this->validator->validateStoredBlob($disk, $storedPath);

            return [
                'importId' => $importId,
                'disk' => $disk,
                'storedPath' => $storedPath,
                'originalName' => $originalName,
            ] + ($validation['valid'] ? [] : ['error' => (string) $validation['message']]);
        }

        /** @var UploadedFile $file */
        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'dat';
        $this->cleanupOldLocalImports($disk);

        return [
            'importId' => $importId,
            'disk' => $disk,
            'storedPath' => $file->storeAs((string) config('vercel.import_prefix', 'catalog-imports'), $importId.'.'.$extension, $disk),
            'originalName' => $file->getClientOriginalName(),
        ];
    }

    private function cleanupOldLocalImports(string $disk): void
    {
        if ($disk !== 'local') {
            return;
        }

        $prefix = (string) config('vercel.import_prefix', 'catalog-imports');
        $threshold = now()->subHours(24)->getTimestamp();

        foreach (Storage::disk($disk)->files($prefix) as $path) {
            if (Storage::disk($disk)->lastModified($path) < $threshold) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
