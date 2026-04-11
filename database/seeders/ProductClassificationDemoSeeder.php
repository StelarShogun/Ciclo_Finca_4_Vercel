<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ProductClassificationAssignmentService;
use Illuminate\Database\Seeder;

/**
 * Productos DEMO además del catálogo principal: uno en raíz (sin clasificaciones) y asignación
 * completa en un neumático (Marca / Uso / Medida / Color) para ver el mismo modelo que en negocio.
 */
class ProductClassificationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::query()->where('status', 'active')->first();
        if (! $supplier) {
            return;
        }
        $brand = Brand::query()->first();

        $root = Category::query()->whereNull('parent_category_id')->orderBy('category_id')->first();
        if ($root) {
            $rootProduct = Product::query()->firstOrCreate(
                [
                    'name' => 'DEMO — Producto categoría raíz (sin clasificaciones)',
                    'category_id' => $root->category_id,
                ],
                [
                    'supplier_id' => $supplier->supplier_id,
                    'description' => 'Semilla: categoría padre; el inventario no muestra selectores de clasificación.',
                    'image' => 'default.png',
                    'sale_price' => 999,
                    'purchase_price' => 100,
                    'stock_current' => 3,
                    'stock_minimum' => 1,
                    'status' => 'active',
                ]
            );
            if ($brand) {
                $rootProduct->brands()->syncWithoutDetaching([$brand->id]);
            }
        }

        $ruedas = $this->findSubcategory('Componentes', 'Ruedas y neumáticos');
        if ($ruedas === null) {
            return;
        }

        $tireProduct = Product::query()
            ->where('category_id', $ruedas->category_id)
            ->where('name', 'Neumático Kenda Small Block Eight 29"')
            ->first();

        $dims = ClassificationDimension::query()
            ->forCategory((int) $ruedas->category_id)
            ->orderBy('sort_order')
            ->with('values')
            ->get();

        if ($tireProduct && $dims->isNotEmpty()) {
            $valueIds = [];
            $pick = [
                'marca' => 'Kenda',
                'uso' => 'Mountainbike',
                'medida' => '29"',
                'color' => 'Rojo',
            ];
            foreach ($dims as $dim) {
                $want = $pick[$dim->slug] ?? null;
                if ($want === null) {
                    $first = $dim->values->first();
                    if ($first instanceof ClassificationValue) {
                        $valueIds[] = $first->id;
                    }

                    continue;
                }
                $match = $dim->values->first(fn (ClassificationValue $v) => $v->value === $want);
                if ($match instanceof ClassificationValue) {
                    $valueIds[] = $match->id;
                } else {
                    $first = $dim->values->first();
                    if ($first instanceof ClassificationValue) {
                        $valueIds[] = $first->id;
                    }
                }
            }
            if ($valueIds !== []) {
                app(ProductClassificationAssignmentService::class)->syncForProduct($tireProduct, $valueIds);
            }
        }

        $firstSub = Category::query()
            ->whereNotNull('parent_category_id')
            ->orderBy('category_id')
            ->first();
        if ($firstSub === null) {
            return;
        }

        $dimsGeneric = ClassificationDimension::query()
            ->forCategory((int) $firstSub->category_id)
            ->orderBy('sort_order')
            ->with('values')
            ->get();
        if ($dimsGeneric->isEmpty()) {
            return;
        }

        $p1 = Product::query()->firstOrCreate(
            [
                'name' => 'DEMO — Subcategoría con clasificaciones',
                'category_id' => $firstSub->category_id,
            ],
            [
                'supplier_id' => $supplier->supplier_id,
                'description' => 'Semilla: color y talla genéricos en la primera subcategoría por ID.',
                'image' => 'default.png',
                'sale_price' => 1999,
                'purchase_price' => 200,
                'stock_current' => 5,
                'stock_minimum' => 1,
                'status' => 'active',
            ]
        );
        if ($brand) {
            $p1->brands()->syncWithoutDetaching([$brand->id]);
        }

        $valueIds = [];
        foreach ($dimsGeneric as $dim) {
            $first = $dim->values->first();
            if ($first instanceof ClassificationValue) {
                $valueIds[] = $first->id;
            }
        }
        if ($valueIds !== []) {
            app(ProductClassificationAssignmentService::class)->syncForProduct($p1, $valueIds);
        }

        $p2 = Product::query()->firstOrCreate(
            [
                'name' => 'DEMO — Subcategoría sin clasificar',
                'category_id' => $firstSub->category_id,
            ],
            [
                'supplier_id' => $supplier->supplier_id,
                'description' => 'Semilla: misma subcategoría, sin valores en pivote.',
                'image' => 'default.png',
                'sale_price' => 1499,
                'purchase_price' => 150,
                'stock_current' => 2,
                'stock_minimum' => 1,
                'status' => 'active',
            ]
        );
        if ($brand) {
            $p2->brands()->syncWithoutDetaching([$brand->id]);
        }
    }

    private function findSubcategory(string $parentName, string $subName): ?Category
    {
        $parent = Category::query()
            ->where('name', $parentName)
            ->whereNull('parent_category_id')
            ->first();
        if ($parent === null) {
            return null;
        }

        return Category::query()
            ->where('parent_category_id', $parent->category_id)
            ->where('name', $subName)
            ->first();
    }
}
