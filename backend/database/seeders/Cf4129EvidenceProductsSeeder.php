<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * CF4-129 — Demo products for PR screenshots (catalog / home / related).
 *
 * Run: php artisan db:seed --class=Cf4129EvidenceProductsSeeder
 */
class Cf4129EvidenceProductsSeeder extends Seeder
{
    private const CATEGORY_PARENT = 'Bicicletas';

    private const CATEGORY_CHILD = 'MTB';

    /** @var array<int, array<string, mixed>> */
    private const ROWS = [
        [
            'name' => 'EV-129 En stock',
            'sku' => 'SKU-EN-01',
            'status' => 'active',
            'stock_current' => 20,
            'is_featured' => true,
        ],
        [
            'name' => 'EV-129 Ultimas',
            'sku' => 'SKU-ULT-01',
            'status' => 'active',
            'stock_current' => 3,
            'is_featured' => true,
        ],
        [
            'name' => 'EV-129 Agotado',
            'sku' => 'SKU-AGO-01',
            'status' => 'active',
            'stock_current' => 0,
            'is_featured' => false,
        ],
        [
            'name' => 'EV-129 Sin SKU',
            'sku' => null,
            'status' => 'active',
            'stock_current' => 15,
            'is_featured' => false,
        ],
        [
            'name' => 'EV-129 No disponible',
            'sku' => 'SKU-NA-01',
            'status' => 'inactive',
            'stock_current' => 10,
            'is_featured' => false,
        ],
    ];

    public function run(): void
    {
        $categoryId = $this->resolveCategoryId();
        $supplierId = $this->resolveSupplierId();

        $anchor = null;

        foreach (self::ROWS as $row) {
            $product = Product::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'category_id' => $categoryId,
                    'supplier_id' => $supplierId,
                    'sku' => $row['sku'],
                    'description' => 'Producto de evidencia CF4-129 (seeder).',
                    'purchase_price' => 80000,
                    'sale_price' => 100000,
                    'stock_current' => $row['stock_current'],
                    'stock_minimum' => 5,
                    'status' => $row['status'],
                    'is_featured' => $row['is_featured'],
                ],
            );

            if ($row['name'] === 'EV-129 En stock') {
                $anchor = $product;
            }
        }

        $this->command?->info('Cf4129EvidenceProductsSeeder: 5 productos listos (misma categoría: '.self::CATEGORY_PARENT.' / '.self::CATEGORY_CHILD.').');
        $this->command?->newLine();
        $this->command?->line('URLs para capturas (http://localhost:8080):');
        $this->command?->line('  Catálogo:     /catalog?search=EV-129');
        $this->command?->line('  Home:         /');
        if ($anchor instanceof Product) {
            $slug = $anchor->clientPublicSlug();
            $this->command?->line("  Detalle (foto 5): /product/{$anchor->product_id}/{$slug}");
        }
    }

    private function resolveCategoryId(): int
    {
        $parent = Category::query()
            ->where('name', self::CATEGORY_PARENT)
            ->whereNull('parent_category_id')
            ->first();

        if ($parent === null) {
            throw new \RuntimeException(
                'No existe la categoría "'.self::CATEGORY_PARENT.'". Ejecuta antes: php artisan db:seed --class=CategorySeeder'
            );
        }

        $child = Category::query()
            ->where('name', self::CATEGORY_CHILD)
            ->where('parent_category_id', $parent->category_id)
            ->first();

        if ($child === null) {
            throw new \RuntimeException(
                'No existe la subcategoría "'.self::CATEGORY_CHILD.'". Ejecuta antes: php artisan db:seed --class=CategorySeeder'
            );
        }

        return (int) $child->category_id;
    }

    private function resolveSupplierId(): int
    {
        $id = Supplier::query()->value('supplier_id');

        if ($id === null) {
            throw new \RuntimeException(
                'No hay proveedores. Ejecuta antes: php artisan db:seed --class=SupplierSeeder'
            );
        }

        return (int) $id;
    }
}
