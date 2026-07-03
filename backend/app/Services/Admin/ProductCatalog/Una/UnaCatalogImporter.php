<?php

namespace App\Services\Admin\ProductCatalog\Una;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Services\Admin\Classifications\ProductClassificationAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UnaCatalogImporter
{
    /** @var array<string, int> */
    private array $brandIds = [];

    /** @var array<string, Product> */
    private array $variantGroupBase = [];

    public function __construct(
        private readonly ProductClassificationAssignmentService $classifications,
        private readonly ?Command $command = null,
    ) {}

    /** @var list<string> */
    private array $importedProductNames = [];

    /**
     * @return array{processed: int, skipped: int, seat_names: list<string>}
     */
    public function import(Supplier $supplier): array
    {
        $anforasRoot = $this->resolveSourceRoot('Anforas');
        $asientosRoot = $this->resolveSourceRoot('ASIENTOS');

        if ($anforasRoot === null || $asientosRoot === null) {
            $this->log('error', 'No se encontraron carpetas UNA (Anforas / ASIENTOS).');

            return ['processed' => 0, 'skipped' => 0];
        }

        $this->syncSeedMirror($anforasRoot, 'anforas');
        $this->syncSeedMirror($asientosRoot, 'asientos');

        $hidratacion = $this->category('Accesorios', 'Hidratación');
        $asientosCat = $this->category('Componentes', 'Asientos');
        $forrosCat = $this->category('Accesorios', 'Forros de asiento');

        $this->ensureDimensions($hidratacion->category_id, [
            ['slug' => 'color', 'label' => 'Color', 'values' => ['Azul', 'Gris', 'Negro', 'Rojo', 'Morado', 'Verde', 'Blanco', 'Amarillo', 'Celeste', 'Fucsia', 'Beige', 'Camuflado', 'Negro-Gris', 'Negro-Blanco']],
            ['slug' => 'capacidad', 'label' => 'Capacidad', 'values' => ['500 ml', '550 ml', '600 ml', '750 ml']],
        ]);
        $this->ensureDimensions($asientosCat->category_id, [
            ['slug' => 'color', 'label' => 'Color', 'values' => ['Azul', 'Gris', 'Negro', 'Rojo', 'Morado', 'Verde', 'Blanco', 'Amarillo', 'Celeste', 'Fucsia', 'Beige', 'Camuflado', 'Negro-Gris', 'Negro-Blanco']],
            ['slug' => 'size', 'label' => 'Talla / rodado', 'values' => ['20"', '26"', 'MTB universal', 'BMX']],
            ['slug' => 'tipo-uso', 'label' => 'Tipo de uso', 'values' => ['MTB', 'Urbano', 'Infantil / BMX', 'Forro']],
        ]);
        $this->ensureDimensions($forrosCat->category_id, [
            ['slug' => 'color', 'label' => 'Color', 'values' => ['Negro', 'Gris']],
        ]);

        $processed = 0;
        $skipped = 0;
        $this->importedProductNames = [];

        foreach ($this->scanImages($anforasRoot) as $rel => $abs) {
            $result = $this->importFile($supplier, 'anforas', $rel, $abs, $hidratacion, $asientosCat, $forrosCat);
            $result ? $processed++ : $skipped++;
        }

        $seatFiles = $this->scanSeatsTwentyNine($asientosRoot);
        $this->log('line', '  Asientos UNA: '.count($seatFiles).' imagen(es) (29 ítems del explorador)');

        foreach ($seatFiles as $rel => $abs) {
            $result = $this->importFile($supplier, 'asientos', $rel, $abs, $hidratacion, $asientosCat, $forrosCat);
            $result ? $processed++ : $skipped++;
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'seat_names' => array_values(array_filter(
                $this->importedProductNames,
                fn ($n) => str_starts_with($n, 'Asiento') || str_starts_with($n, 'Forro'),
            )),
        ];
    }

    /**
     * 29 ítems como en el explorador: BANANA (14 archivos) + FORROS (3) + 4 subcarpetas × 3 grupos.
     * En grupos de 4 subcarpetas se importa 1 imagen representativa por subcarpeta.
     *
     * @return array<string, string> rel => absolute path
     */
    private function scanSeatsTwentyNine(string $root): array
    {
        $out = [];

        foreach ($this->imagesInDirectory($root.'/BANANA') as $abs) {
            $out['BANANA/'.basename($abs)] = $abs;
        }

        foreach ($this->imagesInDirectory($root.'/FORROS') as $abs) {
            $out['FORROS/'.basename($abs)] = $abs;
        }

        foreach (['ASIENTOS', 'INFANTILES Y BMX', 'MTB'] as $group) {
            $groupPath = $root.'/'.$group;
            if (! is_dir($groupPath)) {
                continue;
            }
            foreach (array_filter(scandir($groupPath) ?: [], fn ($d) => $d !== '.' && $d !== '..' && is_dir($groupPath.'/'.$d)) as $sub) {
                $picked = $this->pickRepresentativeImage($groupPath.'/'.$sub);
                if ($picked !== null) {
                    $out[$group.'/'.$sub.'/'.basename($picked)] = $picked;
                }
            }
        }

        ksort($out);

        return $out;
    }

    /**
     * @return list<string> absolute paths
     */
    private function imagesInDirectory(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            if (! is_file($path)) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $files[] = $path;
            }
        }
        sort($files);

        return $files;
    }

    private function pickRepresentativeImage(string $dir): ?string
    {
        $files = $this->imagesInDirectory($dir);
        if ($files === []) {
            foreach (File::files($dir) as $file) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        if ($files === []) {
            return null;
        }

        usort($files, fn (string $a, string $b): int => $this->imagePickScore($a) <=> $this->imagePickScore($b));

        return $files[0];
    }

    private function imagePickScore(string $path): int
    {
        $name = strtolower(basename($path));
        $s = 0;
        if (str_contains($name, 'whatsapp') || str_contains($name, 'imagen de whatsapp')) {
            $s += 100;
        }
        if (str_contains($name, 'asiento') || str_contains($name, 'forro')) {
            $s -= 50;
        }
        if (preg_match('/\.(jpe?g)$/i', $name)) {
            $s -= 5;
        }

        return $s;
    }

    private function resolveSourceRoot(string $folder): ?string
    {
        $candidates = [
            '/home/dilan/Documentos/UNA/'.$folder,
            storage_path('seed/una/'.strtolower($folder)),
        ];

        foreach ($candidates as $path) {
            if (is_dir($path)) {
                return realpath($path) ?: $path;
            }
        }

        return null;
    }

    private function syncSeedMirror(string $sourceRoot, string $targetSubdir): void
    {
        $target = storage_path('seed/una/'.$targetSubdir);
        if (! is_dir($target)) {
            File::makeDirectory($target, 0755, true);
        }

        foreach ($this->scanImages($sourceRoot) as $rel => $abs) {
            $dest = $target.'/'.str_replace('\\', '/', $rel);
            File::ensureDirectoryExists(dirname($dest));
            if (! is_file($dest) || filemtime($abs) > filemtime($dest)) {
                File::copy($abs, $dest);
            }
        }
    }

    /**
     * @return array<string, string> rel => absolute
     */
    private function scanImages(string $root): array
    {
        $files = [];
        foreach (File::allFiles($root) as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }
            $rel = str_replace('\\', '/', $file->getRelativePathname());
            $files[$rel] = $file->getPathname();
        }
        ksort($files);

        return $files;
    }

    private function importFile(
        Supplier $supplier,
        string $collection,
        string $relativePath,
        string $absolutePath,
        Category $hidratacion,
        Category $asientosCat,
        Category $forrosCat,
    ): bool {
        $def = UnaProductResolver::resolve($collection, $relativePath);
        if ($def === null) {
            $this->log('warn', "  ⚠ Sin definición: {$collection}/{$relativePath}");

            return false;
        }

        $category = match ($def['category'] ?? 'asientos') {
            'hidratacion' => $hidratacion,
            'forros' => $forrosCat,
            default => $asientosCat,
        };

        $catId = (int) $category->category_id;
        $classIds = [];
        foreach ($def['classifications'] ?? [] as $slug => $label) {
            $id = $this->valueId($catId, $slug, $label);
            if ($id) {
                $classIds[] = $id;
            }
        }

        $brands = $def['brand'] ?? ['Banana'];
        $this->ensureBrands($brands);

        $product = Product::query()->updateOrCreate(
            ['name' => $def['name']],
            [
                'category_id' => $category->category_id,
                'supplier_id' => $supplier->supplier_id,
                'description' => $def['description'] ?? '',
                'image' => 'default.png',
                'sale_price' => $def['sale'] ?? 9900,
                'purchase_price' => $def['purchase'] ?? 5000,
                'stock_current' => $def['stock'] ?? 10,
                'stock_minimum' => $def['stock_minimum'] ?? 3,
                'status' => 'active',
                'is_featured' => (bool) ($def['featured'] ?? false),
            ],
        );

        $brandIds = array_values(array_filter(array_map(fn ($n) => $this->brandIds[$n] ?? null, $brands)));
        if ($brandIds !== []) {
            $product->brands()->sync($brandIds);
        }

        if ($classIds !== []) {
            $this->classifications->syncForProduct($product, $classIds);
        }

        $product->clearMediaCollection('main_image');
        $product->addMedia($absolutePath)->preservingOriginal()->toMediaCollection('main_image');

        $group = $def['variant_group'] ?? null;
        if ($group) {
            if (! isset($this->variantGroupBase[$group])) {
                $this->variantGroupBase[$group] = $product;
            } else {
                $this->linkVariant($this->variantGroupBase[$group], $product);
            }
        }

        if (! in_array($product->name, $this->importedProductNames, true)) {
            $this->importedProductNames[] = $product->name;
        }
        $this->log('line', '  ✓ '.$product->name);

        return true;
    }

    /**
     * Elimina asientos/forros UNA sobrantes (importación recursiva anterior).
     *
     * @param  list<string>  $keepNames
     */
    public function pruneExtraSeats(array $keepNames): int
    {
        $keep = array_flip($keepNames);
        $removed = 0;

        Product::query()
            ->where(function ($q) {
                $q->where('name', 'like', 'Asiento%')
                    ->orWhere('name', 'like', 'Forro%');
            })
            ->orderBy('product_id')
            ->each(function (Product $product) use ($keep, &$removed) {
                if (isset($keep[$product->name])) {
                    return;
                }
                $this->log('warn', "  ↳ Eliminado duplicado/sobrante: {$product->name}");
                ProductVariant::query()
                    ->where('base_product_id', $product->product_id)
                    ->orWhere('variant_product_id', $product->product_id)
                    ->delete();
                $product->delete();
                $removed++;
            });

        return $removed;
    }

    /**
     * @param  list<string>  $names
     */
    private function ensureBrands(array $names): void
    {
        foreach ($names as $name) {
            if (isset($this->brandIds[$name])) {
                continue;
            }
            $brand = Brand::query()->firstOrCreate(['name' => $name]);
            $this->brandIds[$name] = (int) $brand->id;
        }
    }

    private function category(string $parentName, string $childName): Category
    {
        $parent = Category::query()->where('name', $parentName)->whereNull('parent_category_id')->firstOrFail();

        return Category::query()->firstOrCreate(
            ['name' => $childName, 'parent_category_id' => $parent->category_id],
            ['description' => "Subcategoría de {$parentName}"],
        );
    }

    /**
     * @param  list<array{slug: string, label: string, values: list<string>}>  $defs
     */
    private function ensureDimensions(int $categoryId, array $defs): void
    {
        foreach ($defs as $i => $def) {
            $dim = ClassificationDimension::query()->firstOrCreate(
                ['category_id' => $categoryId, 'slug' => $def['slug']],
                ['label' => $def['label'], 'sort_order' => $i],
            );
            foreach ($def['values'] as $j => $valueLabel) {
                $norm = ClassificationValue::normalizeStoredValue($valueLabel);
                ClassificationValue::query()->firstOrCreate(
                    ['classification_dimension_id' => $dim->id, 'normalized_value' => $norm],
                    ['value' => $valueLabel, 'sort_order' => $j],
                );
            }
        }
    }

    private function valueId(int $categoryId, string $slug, string $label): ?int
    {
        $id = ClassificationValue::query()
            ->whereHas('dimension', fn ($q) => $q->where('category_id', $categoryId)->where('slug', $slug))
            ->where('value', $label)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function linkVariant(Product $base, Product $variant): void
    {
        if ((int) $base->product_id === (int) $variant->product_id) {
            return;
        }

        ProductVariant::query()
            ->where('variant_product_id', $variant->product_id)
            ->delete();

        ProductVariant::query()->firstOrCreate([
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);
    }

    private function log(string $level, string $message): void
    {
        if ($this->command === null) {
            return;
        }
        match ($level) {
            'error' => $this->command->error($message),
            'warn' => $this->command->warn($message),
            default => $this->command->line($message),
        };
    }
}
