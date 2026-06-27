<?php

namespace App\Services\Admin\ProductCatalog;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ZipArchive;

final class ProductCatalogExporter
{
    public const MANIFEST_VERSION = 1;

    /**
     * @return array{version: int, exported_at: string, product_count: int, products: list<array<string, mixed>>}
     */
    public function buildManifest(Builder $query, bool $includeAll = false): array
    {
        $products = (clone $query)
            ->with([
                'category.parent',
                'supplier:supplier_id,name',
                'brands:id,name',
                'classificationValues.dimension',
                'variants:product_id,name,sku',
            ])
            ->when(! $includeAll, fn ($q) => $q->limit(10_000))
            ->get();

        return [
            'version' => self::MANIFEST_VERSION,
            'exported_at' => now()->timezone(config('app.timezone'))->toIso8601String(),
            'product_count' => $products->count(),
            'products' => $products->map(fn (Product $p) => $this->productToArray($p))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productToArray(Product $product): array
    {
        $category = $product->category;
        $parent = $category?->parent;
        $classifications = [];
        foreach ($product->classificationValues as $value) {
            $slug = $value->dimension?->slug;
            if ($slug) {
                $classifications[$slug] = $value->value;
            }
        }

        $key = $this->exportKey($product);
        $main = $product->getFirstMedia('main_image');
        $gallery = $product->getMedia('gallery');

        return [
            'export_key' => $key,
            'product_id' => (int) $product->product_id,
            'sku' => $product->sku,
            'display_sku' => $product->displaySku(),
            'name' => $product->name,
            'description' => $product->description ?? '',
            'category_id' => (int) $product->category_id,
            'category' => $category?->name,
            'parent_category' => $parent?->name,
            'category_path' => array_values(array_filter([
                $parent?->name,
                $category?->name,
            ])),
            'supplier_id' => (int) $product->supplier_id,
            'supplier' => $product->supplier?->name,
            'brands' => $product->brands->pluck('name')->values()->all(),
            'purchase_price' => (float) $product->purchase_price,
            'sale_price' => (float) $product->sale_price,
            'stock_current' => (int) $product->stock_current,
            'stock_minimum' => (int) $product->stock_minimum,
            'status' => $product->status,
            'is_featured' => (bool) $product->is_featured,
            'classifications' => $classifications,
            'variant_export_keys' => $product->variants
                ->map(fn (Product $v) => $this->exportKey($v))
                ->values()
                ->all(),
            'images' => [
                'main' => $main ? 'images/'.$key.'/'.basename($main->file_name) : null,
                'gallery' => $gallery->values()->map(
                    fn ($m, $i) => 'images/'.$key.'/gallery-'.str_pad((string) ($i + 1), 2, '0', '0').'.'.pathinfo($m->file_name, PATHINFO_EXTENSION)
                )->all(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Product>|iterable<int, Product>  $products
     */
    public function writeBundleZip(string $zipPath, iterable $products, array $manifest): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $zip->addFromString('catalog.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        foreach ($products as $product) {
            if (! $product instanceof Product) {
                continue;
            }
            $key = $this->exportKey($product);
            $main = $product->getFirstMedia('main_image');
            if ($main && is_file($main->getPath())) {
                $zip->addFile($main->getPath(), 'images/'.$key.'/'.basename($main->file_name));
            }
            $i = 1;
            foreach ($product->getMedia('gallery') as $media) {
                if (! is_file($media->getPath())) {
                    continue;
                }
                $ext = pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'jpg';
                $zip->addFile($media->getPath(), 'images/'.$key.'/gallery-'.str_pad((string) $i, 2, '0', '0').'.'.$ext);
                $i++;
            }
        }

        $zip->close();
    }

    public function exportKey(Product $product): string
    {
        $custom = trim((string) ($product->sku ?? ''));
        if ($custom !== '') {
            return Str::slug($custom, '-');
        }

        return 'BK-'.str_pad((string) $product->product_id, 3, '0', STR_PAD_LEFT);
    }
}
