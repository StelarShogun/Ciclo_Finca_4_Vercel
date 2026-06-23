<?php

namespace App\Services\Admin\ProductCatalog;

use App\Data\Admin\ProductCatalog\CatalogImportOptions;
use App\Jobs\GenerateCatalogImportMediaConversionsJob;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Services\Admin\Images\ProductImageOptimizerService;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Services\ProductClassificationAssignmentService;
use App\Services\Vercel\QstashPublisher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

final class ProductCatalogImporter
{
    /** @var array<string, int> */
    private array $brandCache = [];

    /** @var array<string, int> */
    private array $supplierCache = [];

    /** @var array<string, int> */
    private array $categoryCache = [];

    /** @var array<int, Category> */
    private array $categoryModelsById = [];

    /** @var array<int, Product> */
    private array $productsById = [];

    /** @var array<string, Product> */
    private array $productsBySku = [];

    /** @var array<string, Product> */
    private array $productsByCategoryAndName = [];

    /** @var array<int, Supplier> */
    private array $supplierModelsById = [];

    /** @var array<string, Product> */
    private array $productsByExportKey = [];

    /** @var list<int> */
    private array $importedMediaIds = [];

    private bool $autoCreateCategories = false;

    private ?CatalogImportOptions $importOptions = null;

    /** @var (callable(int, int, array<string, mixed>): void)|null */
    private $progressCallback = null;

    public function __construct(
        private readonly ProductClassificationAssignmentService $classifications,
        private readonly ProductImageOptimizerService $imageOptimizer,
    ) {}

    /**
     * Callback opcional invocado durante el bucle de filas para reportar avance.
     *
     * @param  (callable(int $processed, int $total, array<string, mixed> $stats): void)|null  $callback
     */
    public function setProgressCallback(?callable $callback): void
    {
        $this->progressCallback = $callback;
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function reportProgress(int $processed, int $total, array $stats): void
    {
        if ($this->progressCallback === null) {
            return;
        }

        ($this->progressCallback)($processed, $total, $stats);
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int}
     */
    public function import(UploadedFile $file, ?string $extractedDir = null, ?CatalogImportOptions $options = null): array
    {
        $options ??= CatalogImportOptions::default();

        return $options->runImportStats(function () use ($file, $extractedDir, $options) {
            $this->importOptions = $options;

            try {
                return $this->runImport($file, $extractedDir);
            } finally {
                $this->importOptions = null;
            }
        });
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int}
     */
    private function runImport(UploadedFile $file, ?string $extractedDir): array
    {
        $startedAt = hrtime(true);
        $this->importedMediaIds = [];
        $this->autoCreateCategories = false;
        $this->productsById = [];
        $this->productsBySku = [];
        $this->productsByCategoryAndName = [];
        /** @var array{created: int, updated: int, skipped: int, errors: list<string>, media_conversions_queued: int, rows_total: int, duration_ms: int, media_count: int} $stats */
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'media_conversions_queued' => 0,
            'rows_total' => 0,
            'duration_ms' => 0,
            'media_count' => 0,
        ];
        $bundleDir = $extractedDir;

        if ($bundleDir === null && strtolower($file->getClientOriginalExtension()) === 'zip') {
            $bundleDir = $this->extractBundleZip($file);
        }

        $this->autoCreateCategories = $bundleDir !== null;

        $parser = new ProductCatalogFileParser;
        $rows = $bundleDir !== null
            ? $this->rowsFromBundle($bundleDir)
            : $parser->parse($file);

        if ($rows === []) {
            $stats['errors'][] = 'No se encontraron productos reconocibles en el archivo.';
            $stats['duration_ms'] = (int) ((hrtime(true) - $startedAt) / 1_000_000);

            return $stats;
        }

        $stats['rows_total'] = count($rows);
        $this->warmLookupCaches();

        $total = count($rows);
        $this->reportProgress(0, $total, $stats);

        DB::transaction(function () use ($rows, $bundleDir, $total, &$stats) {
            foreach ($rows as $index => $row) {
                try {
                    $result = $this->importRow($row, $bundleDir);
                    $stats[$result]++;
                } catch (\Throwable $e) {
                    $stats['skipped']++;
                    $stats['errors'][] = 'Fila '.($index + 1).': '.$e->getMessage();
                    Log::warning('product_catalog_import_row_failed', [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                    ]);
                }

                $this->reportProgress($index + 1, $total, $stats);
            }
            $this->linkVariantsFromRows($rows);
        });

        ClientStorefrontCache::forgetAfterProductMutation();

        if ($bundleDir !== null && str_starts_with($bundleDir, sys_get_temp_dir())) {
            $this->deleteDirectory($bundleDir);
        }

        $mediaIds = array_values(array_unique($this->importedMediaIds));
        $stats['media_count'] = count($mediaIds);
        if ($mediaIds !== []) {
            if (config('vercel.enabled')) {
                app(QstashPublisher::class)->publish(
                    'internal/vercel/jobs/media-conversions',
                    ['mediaIds' => $mediaIds],
                    forwardHeaders: ['X-Internal-Key' => (string) config('app.deploy_secret')],
                );
            } else {
                GenerateCatalogImportMediaConversionsJob::dispatch($mediaIds)->afterResponse();
            }
            $stats['media_conversions_queued'] = count($mediaIds);
        }

        $stats['duration_ms'] = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        return $stats;
    }

    private function warmLookupCaches(): void
    {
        foreach (Brand::query()->pluck('id', 'name') as $name => $id) {
            $this->brandCache[mb_strtolower((string) $name)] = (int) $id;
        }

        foreach (Supplier::query()->get() as $supplier) {
            $this->supplierCache[mb_strtolower($supplier->name)] = (int) $supplier->supplier_id;
            $this->supplierModelsById[(int) $supplier->supplier_id] = $supplier;
        }

        foreach (Category::query()->get(['category_id', 'name', 'parent_category_id']) as $category) {
            $this->categoryModelsById[(int) $category->category_id] = $category;
            if ($category->parent_category_id === null) {
                $this->categoryCache['parent|'.mb_strtolower($category->name)] = (int) $category->category_id;
            } else {
                $this->categoryCache['sub|'.mb_strtolower($category->name)] = (int) $category->category_id;
            }
        }

        foreach (Product::query()->get(['product_id', 'category_id', 'name', 'sku']) as $product) {
            $this->rememberProduct($product);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importRow(array $row, ?string $bundleDir): string
    {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Falta el nombre del producto.');
        }

        $category = $this->resolveCategory($row);
        if ($category === null) {
            $pathLabel = $this->categoryPathLabel($row);
            throw new \InvalidArgumentException($pathLabel !== ''
                ? "No se pudo resolver la categoría/subcategoría ({$pathLabel})."
                : 'No se pudo resolver la categoría/subcategoría.');
        }

        $supplier = $this->resolveSupplier($row);
        $purchase = $this->parseMoney($row['purchase_price'] ?? null) ?? 0.0;
        $sale = $this->parseMoney($row['sale_price'] ?? null) ?? max($purchase * 1.35, 1000);
        if ($sale <= $purchase) {
            $sale = $purchase + max(500, $purchase * 0.15);
        }

        $payload = [
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => $name,
            'sku' => $this->nullableString($row['sku'] ?? null),
            'description' => (string) ($row['description'] ?? ''),
            'purchase_price' => round($purchase, 2),
            'sale_price' => round($sale, 2),
            'stock_current' => max(0, (int) ($row['stock_current'] ?? 0)),
            'stock_minimum' => max(0, (int) ($row['stock_minimum'] ?? 3)),
            'status' => $this->parseStatus($row['status'] ?? 'active'),
            'is_featured' => filter_var($row['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'image' => 'default.png',
        ];

        $product = $this->findExistingProduct($row, $category->category_id, $name);
        $action = 'created';

        if ($product) {
            $product->update($payload);
            $action = 'updated';
        } else {
            $product = Product::query()->create($payload);
        }

        $this->rememberProduct($product);
        $this->syncBrands($product, $row);
        $this->syncClassifications($product, $row, $category);
        $this->importImages($product, $row, $bundleDir);

        $exportKey = (string) ($row['export_key'] ?? $this->exportKeyFor($product));
        $this->productsByExportKey[$exportKey] = $product;

        return $action;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function linkVariantsFromRows(array $rows): void
    {
        foreach ($rows as $row) {
            $keys = $row['variant_export_keys'] ?? [];
            if (! is_array($keys) || $keys === []) {
                continue;
            }
            $baseKey = (string) ($row['export_key'] ?? '');
            $base = $this->productsByExportKey[$baseKey] ?? null;
            if (! $base instanceof Product) {
                continue;
            }
            foreach ($keys as $variantKey) {
                $variant = $this->productsByExportKey[(string) $variantKey] ?? null;
                if ($variant instanceof Product && (int) $variant->product_id !== (int) $base->product_id) {
                    ProductVariant::query()
                        ->where('variant_product_id', $variant->product_id)
                        ->delete();
                    ProductVariant::query()->firstOrCreate([
                        'base_product_id' => $base->product_id,
                        'variant_product_id' => $variant->product_id,
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function findExistingProduct(array $row, int $categoryId, string $name): ?Product
    {
        if (! empty($row['product_id'])) {
            $productId = (int) $row['product_id'];
            if (isset($this->productsById[$productId])) {
                return $this->productsById[$productId];
            }
        }

        $sku = $this->nullableString($row['sku'] ?? null);
        if ($sku !== null) {
            $skuKey = mb_strtolower($sku);
            if (isset($this->productsBySku[$skuKey])) {
                return $this->productsBySku[$skuKey];
            }
        }

        $nameKey = $categoryId.'|'.mb_strtolower($name);
        if (isset($this->productsByCategoryAndName[$nameKey])) {
            return $this->productsByCategoryAndName[$nameKey];
        }

        return null;
    }

    private function rememberProduct(Product $product): void
    {
        $productId = (int) $product->product_id;
        $this->productsById[$productId] = $product;

        $sku = $this->nullableString($product->sku);
        if ($sku !== null) {
            $this->productsBySku[mb_strtolower($sku)] = $product;
        }

        $this->productsByCategoryAndName[(int) $product->category_id.'|'.mb_strtolower((string) $product->name)] = $product;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveCategory(array $row): ?Category
    {
        if (! empty($row['category_id'])) {
            $categoryId = (int) $row['category_id'];
            if (isset($this->categoryModelsById[$categoryId])) {
                return $this->categoryModelsById[$categoryId];
            }
        }

        $path = $row['category_path'] ?? null;
        if (is_array($path) && $path !== []) {
            return $this->categoryByPath($path);
        }

        $subName = trim((string) ($row['category'] ?? ''));
        $parentName = trim((string) ($row['parent_category'] ?? ''));

        if ($subName !== '' && $parentName !== '') {
            return $this->categoryByPath([$parentName, $subName]);
        }

        if ($subName !== '') {
            $cacheKey = 'sub|'.mb_strtolower($subName);
            if (isset($this->categoryCache[$cacheKey])) {
                return $this->categoryModelsById[$this->categoryCache[$cacheKey]] ?? null;
            }
            $cat = Category::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($subName)])
                ->whereNotNull('parent_category_id')
                ->first();
            if ($cat) {
                $this->rememberCategoryInCache($cat);

                return $cat;
            }

            $parentCacheKey = 'parent|'.mb_strtolower($subName);
            if (isset($this->categoryCache[$parentCacheKey])) {
                return $this->categoryModelsById[$this->categoryCache[$parentCacheKey]] ?? null;
            }
            $parentCat = Category::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($subName)])
                ->whereNull('parent_category_id')
                ->first();
            if ($parentCat) {
                $this->rememberCategoryInCache($parentCat);

                return $parentCat;
            }
        }

        return Category::query()->whereNotNull('parent_category_id')->orderBy('category_id')->first()
            ?? Category::query()->whereNull('parent_category_id')->orderBy('category_id')->first();
    }

    /**
     * @param  list<string>  $path
     */
    private function categoryByPath(array $path): ?Category
    {
        $path = array_values(array_filter(array_map(fn ($p) => trim((string) $p), $path)));
        if ($path === []) {
            return null;
        }

        $cacheKey = implode('>', array_map('mb_strtolower', $path));
        if (isset($this->categoryCache[$cacheKey])) {
            return $this->categoryModelsById[$this->categoryCache[$cacheKey]] ?? null;
        }

        $parent = null;
        $current = null;
        foreach ($path as $segment) {
            $query = Category::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($segment)]);
            if ($parent === null) {
                $query->whereNull('parent_category_id');
            } else {
                $query->where('parent_category_id', $parent->category_id);
            }
            $current = $query->first();
            if (! $current && $this->autoCreateCategories) {
                $current = Category::query()->create([
                    'name' => $segment,
                    'description' => null,
                    'parent_category_id' => $parent?->category_id,
                ]);
                $this->rememberCategoryInCache($current);
            }
            if (! $current) {
                return null;
            }
            $this->rememberCategoryInCache($current);
            $parent = $current;
        }

        $this->categoryCache[$cacheKey] = (int) $current->category_id;

        return $current;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function categoryPathLabel(array $row): string
    {
        $path = $row['category_path'] ?? null;
        if (is_array($path) && $path !== []) {
            return implode(' > ', array_values(array_filter(array_map(
                fn ($segment) => trim((string) $segment),
                $path,
            ))));
        }

        $subName = trim((string) ($row['category'] ?? ''));
        $parentName = trim((string) ($row['parent_category'] ?? ''));

        if ($subName !== '' && $parentName !== '') {
            return $parentName.' > '.$subName;
        }

        if ($subName !== '') {
            return $subName;
        }

        if (! empty($row['category_id'])) {
            return 'id '.(int) $row['category_id'];
        }

        return '';
    }

    private function rememberCategoryInCache(Category $category): void
    {
        $this->categoryModelsById[(int) $category->category_id] = $category;
        if ($category->parent_category_id === null) {
            $this->categoryCache['parent|'.mb_strtolower($category->name)] = (int) $category->category_id;
        } else {
            $this->categoryCache['sub|'.mb_strtolower($category->name)] = (int) $category->category_id;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveSupplier(array $row): Supplier
    {
        $name = trim((string) ($row['supplier'] ?? ''));
        if ($name === '') {
            return Supplier::query()->where('status', 'active')->orderBy('supplier_id')->first()
                ?? throw new \RuntimeException('No hay proveedor activo en el sistema.');
        }

        $key = mb_strtolower($name);
        if (isset($this->supplierCache[$key])) {
            $supplierId = $this->supplierCache[$key];

            return $this->supplierModelsById[$supplierId]
                ?? Supplier::query()->findOrFail($supplierId);
        }

        $supplier = Supplier::query()->firstOrCreate(
            ['name' => $name],
            ['status' => 'active', 'primary_contact' => $name, 'email' => '', 'phone' => ''],
        );
        $this->supplierCache[$key] = (int) $supplier->supplier_id;
        $this->supplierModelsById[(int) $supplier->supplier_id] = $supplier;

        return $supplier;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function syncBrands(Product $product, array $row): void
    {
        $names = [];
        if (isset($row['brands']) && is_array($row['brands'])) {
            $names = array_map('strval', $row['brands']);
        } elseif (! empty($row['brand'])) {
            $names = preg_split('/[,;|]/', (string) $row['brand']) ?: [];
        }

        $names = array_values(array_filter(array_map('trim', $names)));
        if ($names === []) {
            return;
        }

        $ids = [];
        foreach ($names as $name) {
            $key = mb_strtolower($name);
            if (! isset($this->brandCache[$key])) {
                $this->brandCache[$key] = (int) Brand::query()->firstOrCreate(['name' => $name])->id;
            }
            $ids[] = $this->brandCache[$key];
        }

        $product->brands()->sync(array_slice($ids, 0, 1));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function syncClassifications(Product $product, array $row, ?Category $category = null): void
    {
        $defs = $row['classifications'] ?? [];
        if (! is_array($defs) || $defs === []) {
            return;
        }

        $category ??= $product->category;
        if (! $category || $category->parent_category_id === null) {
            return;
        }

        $valueIds = [];
        foreach ($defs as $slug => $label) {
            if (! is_string($slug) || ! is_scalar($label) || trim((string) $label) === '') {
                continue;
            }
            $dim = ClassificationDimension::query()->firstOrCreate(
                ['category_id' => $category->category_id, 'slug' => Str::slug($slug, '-')],
                ['label' => ucfirst(str_replace('-', ' ', $slug)), 'sort_order' => 0],
            );
            $norm = ClassificationValue::normalizeStoredValue((string) $label);
            $val = ClassificationValue::query()->firstOrCreate(
                ['classification_dimension_id' => $dim->id, 'normalized_value' => $norm],
                ['value' => trim((string) $label), 'sort_order' => 0],
            );
            $valueIds[] = (int) $val->id;
        }

        if ($valueIds !== []) {
            $this->classifications->syncForProduct($product, $valueIds);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importImages(Product $product, array $row, ?string $bundleDir): void
    {
        if ($bundleDir === null) {
            return;
        }

        $images = $row['images'] ?? [];
        if (! is_array($images)) {
            return;
        }

        $mainRel = $images['main'] ?? null;
        if (is_string($mainRel) && $mainRel !== '') {
            $path = $bundleDir.'/'.ltrim($mainRel, '/');
            if (is_file($path)) {
                $product->clearMediaCollection('main_image');
                $this->attachMedia($product, $path, 'main_image');
            }
        }

        $gallery = $images['gallery'] ?? [];
        if (is_array($gallery) && $gallery !== []) {
            $product->clearMediaCollection('gallery');
            foreach ($gallery as $rel) {
                if (! is_string($rel)) {
                    continue;
                }
                $path = $bundleDir.'/'.ltrim($rel, '/');
                if (is_file($path)) {
                    $this->attachMedia($product, $path, 'gallery');
                }
            }
        }
    }

    private function attachMedia(Product $product, string $absolutePath, string $collection): void
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return;
        }

        if ($this->importOptions?->fastImport ?? CatalogImportState::isFastImport()) {
            if (@getimagesize($absolutePath) === false) {
                return;
            }

            $media = $product->addMedia($absolutePath)->preservingOriginal()->toMediaCollection($collection);
            $this->importedMediaIds[] = (int) $media->id;

            return;
        }

        try {
            $sanitized = $this->imageOptimizer->sanitizePath($absolutePath);
        } catch (\Throwable) {
            return;
        }

        $product->addMedia($sanitized)->preservingOriginal()->toMediaCollection($collection);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsFromBundle(string $dir): array
    {
        $manifestPath = $dir.'/catalog.json';
        if (! is_file($manifestPath)) {
            throw new \InvalidArgumentException('catalog.json no encontrado en el paquete.');
        }

        $data = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($data) || ! isset($data['products']) || ! is_array($data['products'])) {
            throw new \InvalidArgumentException('catalog.json inválido.');
        }

        return $data['products'];
    }

    private function extractBundleZip(UploadedFile $file): string
    {
        $dir = sys_get_temp_dir().'/cf4-import-'.Str::uuid();
        if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear carpeta temporal.');
        }

        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw new \InvalidArgumentException('ZIP inválido.');
        }
        $zip->extractTo($dir);
        $zip->close();

        return $dir;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    private function parseMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $s = preg_replace('/[^\d.,\-]/', '', (string) $value) ?? '';
        if ($s === '') {
            return null;
        }
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    private function parseStatus(mixed $raw): string
    {
        return Product::canonicalStatus(is_scalar($raw) ? (string) $raw : 'active');
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    private function exportKeyFor(Product $product): string
    {
        return (new ProductCatalogExporter)->exportKey($product);
    }
}
