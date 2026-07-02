<?php

namespace App\Services\Api;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;

/**
 * Traduce IDs internos (autoincrementales) a IDs públicos (ULID) en los
 * payloads del API cliente, y resuelve el camino inverso en la entrada.
 * El SPA nunca ve una clave interna: no hay enumeración posible.
 */
final class PublicIdMapper
{
    /** entity => [model, pk] */
    private const ENTITIES = [
        'product' => [Product::class, 'product_id'],
        'category' => [Category::class, 'category_id'],
        'brand' => [Brand::class, 'id'],
        'sale' => [Sale::class, 'sale_id'],
    ];

    /** Rutas (dot paths, * = cada elemento) => entidad, por payload. */
    private const SPECS = [
        'catalog' => [
            'products.*.id' => 'product',
            'products.*.category.id' => 'category',
            'products.*.parentCategory.id' => 'category',
            'products.*.brands.*.id' => 'brand',
            'categories.*.id' => 'category',
            'categories.*.children.*.id' => 'category',
            'brands.*.id' => 'brand',
            'filters.categoryId' => 'category',
            'filters.brandId' => 'brand',
            'selectedCategory.id' => 'category',
        ],
        'home' => [
            'featuredProducts.*.id' => 'product',
            'categories.*.id' => 'category',
            'categories.*.children.*.id' => 'category',
        ],
        'productDetail' => [
            'product.id' => 'product',
            'taxonomy.parentCategory.id' => 'category',
            'taxonomy.subcategory.id' => 'category',
            'relatedProducts.*.id' => 'product',
        ],
        'cart' => [
            'items.*.productId' => 'product',
        ],
        'favorites' => [
            'favorites.*.product_id' => 'product',
        ],
        'invoices' => [
            'orders.*.id' => 'sale',
        ],
        'invoiceDetail' => [
            'id' => 'sale',
            'items.*.productId' => 'product',
        ],
        'suggestionProduct' => [
            'id' => 'product',
        ],
        'suggestionCategory' => [
            'id' => 'category',
        ],
    ];

    /** @var array<string, array<int, string>> caché por request: entity => [id => public] */
    private array $maps = [];

    /** Aplica el spec nombrado y limpia URLs embebidas con ids numéricos. */
    public function map(string $spec, array $payload): array
    {
        $paths = self::SPECS[$spec] ?? [];

        // 1) Recolectar ids internos por entidad.
        $wanted = [];
        foreach ($paths as $path => $entity) {
            foreach ($this->collect($payload, explode('.', $path)) as $value) {
                if (is_numeric($value)) {
                    $wanted[$entity][] = (int) $value;
                }
            }
        }
        $this->load($wanted);

        // 2) Sustituir en el payload.
        foreach ($paths as $path => $entity) {
            $this->replace($payload, explode('.', $path), $entity);
        }

        // 3) URLs embebidas (payloads compartidos con el web viejo).
        $this->scrubUrls($payload);

        return $payload;
    }

    /** ID interno desde un ID público; null si no existe. */
    public function internalId(string $entity, mixed $publicId): ?int
    {
        if (! is_string($publicId) || $publicId === '' || strlen($publicId) > 26) {
            return null;
        }

        [$model, $pk] = self::ENTITIES[$entity];
        $id = $model::query()->where('public_id', $publicId)->value($pk);

        return $id === null ? null : (int) $id;
    }

    /** @param array<string, list<int>> $wanted */
    private function load(array $wanted): void
    {
        foreach ($wanted as $entity => $ids) {
            [$model, $pk] = self::ENTITIES[$entity];
            $missing = array_diff(array_unique($ids), array_keys($this->maps[$entity] ?? []));
            if ($missing === []) {
                continue;
            }
            $rows = $model::query()->whereIn($pk, $missing)->pluck('public_id', $pk);
            foreach ($rows as $id => $public) {
                $this->maps[$entity][(int) $id] = (string) $public;
            }
        }
    }

    /** @return list<mixed> valores encontrados en el path */
    private function collect(mixed $node, array $segments): array
    {
        if (! is_array($node)) {
            return [];
        }
        $segment = array_shift($segments);

        if ($segment === '*') {
            $found = [];
            foreach ($node as $child) {
                $found = [...$found, ...$this->collect($child, $segments)];
            }

            return $found;
        }

        if (! array_key_exists($segment, $node)) {
            return [];
        }
        if ($segments === []) {
            return [$node[$segment]];
        }

        return $this->collect($node[$segment], $segments);
    }

    private function replace(array &$node, array $segments, string $entity): void
    {
        $segment = array_shift($segments);

        if ($segment === '*') {
            foreach ($node as &$child) {
                if (is_array($child)) {
                    $this->replace($child, $segments, $entity);
                }
            }

            return;
        }

        if (! array_key_exists($segment, $node)) {
            return;
        }

        if ($segments === []) {
            $value = $node[$segment];
            if (is_numeric($value)) {
                $node[$segment] = $this->maps[$entity][(int) $value] ?? null;
            }

            return;
        }

        if (is_array($node[$segment])) {
            $this->replace($node[$segment], $segments, $entity);
        }
    }

    /**
     * Reescribe URLs embebidas que traen ids numéricos (vienen de builders
     * compartidos con el web viejo): /products/{n} y ?category_id={n}.
     */
    private function scrubUrls(array &$node): void
    {
        foreach ($node as $key => &$value) {
            if (is_array($value)) {
                $this->scrubUrls($value);
                continue;
            }
            if (! is_string($value) || ! in_array($key, ['url', 'url_parent', 'productUrl'], true)) {
                continue;
            }

            $value = preg_replace_callback('#/products/(\d+)#', function (array $m): string {
                $this->load(['product' => [(int) $m[1]]]);

                return '/product/'.($this->maps['product'][(int) $m[1]] ?? '');
            }, $value) ?? $value;

            $value = preg_replace_callback('#category_id=(\d+)#', function (array $m): string {
                $this->load(['category' => [(int) $m[1]]]);

                return 'category_id='.($this->maps['category'][(int) $m[1]] ?? '');
            }, $value) ?? $value;
        }
    }
}
