<?php

namespace App\Services\Admin\Products;

use App\Http\Requests\Admin\Products\StoreProductRequest;
use App\Http\Requests\Admin\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\Admin\Images\ProductImageOptimizerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductMediaService
{
    /**
     * @return array{success: true, message: string, url: string}
     */
    public function promoteGalleryImageToMain(Product $product, int $mediaId): array
    {
        $mediaItem = $product->media()->where('id', $mediaId)->firstOrFail();

        $product->addMedia($mediaItem->getPath())
            ->preservingOriginal()
            ->toMediaCollection('main_image');

        return [
            'success' => true,
            'message' => 'Imagen promovida como principal.',
            'url' => $product->getFirstMediaUrl('main_image'),
        ];
    }

    /**
     * @return array{success: true, message: string}
     */
    public function removeGalleryImage(Product $product, int $mediaId): array
    {
        $mediaItem = $product->media()->where('id', $mediaId)->firstOrFail();
        $mediaItem->delete();

        return [
            'success' => true,
            'message' => 'Imagen eliminada de la galería.',
        ];
    }

    public function attachFromStoreRequest(Product $product, StoreProductRequest $request): void
    {
        $slug = $this->productImageSlug($product);
        $folderPath = $this->productImageFolderPath($product);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext = $file->extension() ?: $file->getClientOriginalExtension();
            $filename = $slug.'_main.'.$ext;
            $this->moveUploadedProductImage($file, $folderPath, $filename, 'image');
            $this->addSanitizedMedia($product, $folderPath.'/'.$filename, 'main_image');
        }

        if ($request->hasFile('images')) {
            $i = 2;
            foreach ($request->file('images') as $file) {
                if (! $file->isValid() || ! str_starts_with($file->getMimeType() ?? '', 'image/')) {
                    continue;
                }
                $ext = $file->extension() ?: $file->getClientOriginalExtension();
                $filename = $slug.'_'.$i.'.'.$ext;
                $this->moveUploadedProductImage($file, $folderPath, $filename, 'images');
                $this->addSanitizedMedia($product, $folderPath.'/'.$filename, 'gallery');
                $i++;
            }
        }
    }

    public function syncFromUpdateRequest(Product $product, UpdateProductRequest $request): void
    {
        $slug = $this->productImageSlug($product);
        $folderPath = $this->productImageFolderPath($product);

        if ($request->boolean('remove_main_image') && ! $request->hasFile('image')) {
            foreach (glob($folderPath.'/'.$slug.'_main.*') ?: [] as $old) {
                @unlink($old);
            }
            $product->clearMediaCollection('main_image');
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext = $file->extension() ?: $file->getClientOriginalExtension();
            foreach (glob($folderPath.'/'.$slug.'_main.*') ?: [] as $old) {
                @unlink($old);
            }
            $filename = $slug.'_main.'.$ext;
            $this->moveUploadedProductImage($file, $folderPath, $filename, 'image');
            $this->addSanitizedMedia($product, $folderPath.'/'.$filename, 'main_image');
        }

        if ($request->hasFile('images')) {
            foreach (glob($folderPath.'/'.$slug.'_[0-9]*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [] as $old) {
                @unlink($old);
            }
            $product->clearMediaCollection('gallery');
            $i = 2;
            foreach ($request->file('images') as $file) {
                if (! $file->isValid() || ! str_starts_with($file->getMimeType() ?? '', 'image/')) {
                    continue;
                }
                $ext = $file->extension() ?: $file->getClientOriginalExtension();
                $filename = $slug.'_'.$i.'.'.$ext;
                $this->moveUploadedProductImage($file, $folderPath, $filename, 'images');
                $this->addSanitizedMedia($product, $folderPath.'/'.$filename, 'gallery');
                $i++;
            }
        }
    }

    public function productImageSlug(Product $product): string
    {
        return Str::slug($product->name, '_');
    }

    public function productImageFolderPath(Product $product): string
    {
        $slug = $this->productImageSlug($product);
        $folderPath = public_path('images/'.$slug);

        if (is_dir($folderPath) || is_writable(dirname($folderPath))) {
            File::ensureDirectoryExists($folderPath, 0755);

            if (is_dir($folderPath) && ! is_writable($folderPath)) {
                @chmod($folderPath, 0755);
            }

            if (is_writable($folderPath)) {
                return $folderPath;
            }
        }

        $fallbackPath = storage_path('app/product-images/'.$slug);
        File::ensureDirectoryExists($fallbackPath, 0755);

        return $fallbackPath;
    }

    public function moveUploadedProductImage(
        UploadedFile $file,
        string $folderPath,
        string $filename,
        string $field
    ): void {
        $destination = rtrim($folderPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;

        if (is_file($destination)) {
            @unlink($destination);
        }

        try {
            $file->move($folderPath, $filename);
        } catch (\Throwable $e) {
            if (is_file($destination)) {
                @unlink($destination);
            }

            if (is_readable($file->getPathname()) && @copy($file->getPathname(), $destination)) {
                @unlink($file->getPathname());

                return;
            }

            Log::warning('cf4_image_sanitize_failed', [
                'path' => $destination,
                'collection' => $field === 'image' ? 'main_image' : 'gallery',
                'stage' => 'move',
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                $field => ['No se pudo procesar la imagen de forma segura. Usá JPEG, PNG, GIF o WebP.'],
            ]);
        }
    }

    public function addSanitizedMedia(Product $product, string $absolutePath, string $collection): void
    {
        $optimizer = app(ProductImageOptimizerService::class);
        $field = $collection === 'main_image' ? 'image' : 'images';

        try {
            $sanitizedPath = $optimizer->sanitizePath($absolutePath);
        } catch (\Throwable $e) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            Log::warning('cf4_image_sanitize_failed', [
                'path' => $absolutePath,
                'collection' => $collection,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                $field => ['No se pudo procesar la imagen de forma segura. Usá JPEG, PNG, GIF o WebP.'],
            ]);
        }

        $product->addMedia($sanitizedPath)
            ->preservingOriginal()
            ->toMediaCollection($collection);
    }
}
