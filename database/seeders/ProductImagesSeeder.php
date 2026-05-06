<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProductImagesSeeder extends Seeder
{
    public function run(): void
    {
        $imagesBase = public_path('images');

        if (! is_dir($imagesBase)) {
            $this->command?->warn("ProductImagesSeeder: directorio \"{$imagesBase}\" no encontrado.");

            return;
        }

        $folders = File::directories($imagesBase);

        if (empty($folders)) {
            $this->command?->warn('ProductImagesSeeder: no hay subcarpetas en public/images/.');

            return;
        }

        $assigned = 0;
        $skipped = 0;

        foreach ($folders as $folder) {
            $productName = basename($folder);

            $product = Product::where('name', $productName)->first();

            if (! $product) {
                $this->command?->warn("  ⚠ Sin coincidencia de producto para carpeta: \"{$productName}\"");
                $skipped++;

                continue;
            }

            $mainFile = $this->findBySuffix($folder, '_main');
            $galleryFile = $this->findBySuffix($folder, '_2');

            if (! $mainFile && ! $galleryFile) {
                $this->command?->warn("  ⚠ Sin imágenes válidas en: \"{$productName}\"");
                $skipped++;

                continue;
            }

            // Limpiar media existente para evitar duplicados en re-ejecuciones
            $product->clearMediaCollection('main_image');
            $product->clearMediaCollection('gallery');

            if ($mainFile) {
                $product->addMedia($mainFile)
                    ->preservingOriginal()
                    ->toMediaCollection('main_image');
            }

            if ($galleryFile) {
                $product->addMedia($galleryFile)
                    ->preservingOriginal()
                    ->toMediaCollection('gallery');
            }

            $assigned++;
            $this->command?->line("  ✓ {$productName}");
        }

        $this->command?->info("ProductImagesSeeder: {$assigned} producto(s) con imágenes asignadas, {$skipped} omitido(s).");
    }

    /**
     * Busca dentro de $dir el primer archivo cuyo nombre (sin extensión)
     * termine con $suffix, soportando webp, png, jpg, jpeg.
     */
    private function findBySuffix(string $dir, string $suffix): ?string
    {
        foreach (File::files($dir) as $file) {
            $nameWithoutExt = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $ext = strtolower($file->getExtension());

            if (! in_array($ext, ['webp', 'png', 'jpg', 'jpeg'], true)) {
                continue;
            }

            if (str_ends_with(strtolower($nameWithoutExt), strtolower($suffix))) {
                return $file->getPathname();
            }
        }

        return null;
    }
}
