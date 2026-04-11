<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'categoria_padre' => 'Bicicletas',
                'subcategoria' => 'MTB',
                'proveedor_id' => 1,
                'nombre' => 'Trek Fuel EX 8',
                'descripcion' => 'Bicicleta de montaña full suspension 29" con transmisión Shimano XT',
                'precio_compra' => 1200000,
                'precio_venta' => 1500000,
                'stock_actual' => 5,
                'stock_minimo' => 5,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Bicicletas',
                'subcategoria' => 'Ruta / Gravel',
                'proveedor_id' => 2,
                'nombre' => 'Specialized Tarmac SL7',
                'descripcion' => 'Bicicleta de ruta de carbono con grupo Shimano 105',
                'precio_compra' => 1800000,
                'precio_venta' => 2200000,
                'stock_actual' => 5,
                'stock_minimo' => 5,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Bicicletas',
                'subcategoria' => 'Urbana / Híbrida',
                'proveedor_id' => 5,
                'nombre' => 'Giant Escape 3',
                'descripcion' => 'Bicicleta urbana híbrida con frenos de disco',
                'precio_compra' => 180000,
                'precio_venta' => 250000,
                'stock_actual' => 8,
                'stock_minimo' => 5,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Componentes',
                'subcategoria' => 'Transmisión',
                'proveedor_id' => 3,
                'nombre' => 'Shimano Deore XT M8100',
                'descripcion' => 'Grupo de transmisión completo 12 velocidades',
                'precio_compra' => 180000,
                'precio_venta' => 250000,
                'stock_actual' => 5,
                'stock_minimo' => 5,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Componentes',
                'subcategoria' => 'Transmisión',
                'proveedor_id' => 4,
                'nombre' => 'SRAM GX Eagle',
                'descripcion' => 'Grupo de transmisión 12 velocidades para MTB',
                'precio_compra' => 220000,
                'precio_venta' => 300000,
                'stock_actual' => 5,
                'stock_minimo' => 5,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Componentes',
                'subcategoria' => 'Frenos',
                'proveedor_id' => 3,
                'nombre' => 'Frenos de Disco Shimano BR-MT200',
                'descripcion' => 'Frenos de disco hidráulicos para MTB',
                'precio_compra' => 45000,
                'precio_venta' => 65000,
                'stock_actual' => 12,
                'stock_minimo' => 6,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Componentes',
                'subcategoria' => 'Ruedas y neumáticos',
                'proveedor_id' => 3,
                'nombre' => 'Neumático Kenda Small Block Eight 29"',
                'descripcion' => 'Neumático MTB 29"; ejemplo de marca + uso + medida en clasificaciones.',
                'precio_compra' => 28000,
                'precio_venta' => 45000,
                'stock_actual' => 20,
                'stock_minimo' => 8,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Accesorios',
                'subcategoria' => 'Iluminación',
                'proveedor_id' => 7,
                'nombre' => 'Luces LED Cateye Volt 400',
                'descripcion' => 'Luz delantera LED recargable 400 lumens',
                'precio_compra' => 25000,
                'precio_venta' => 40000,
                'stock_actual' => 20,
                'stock_minimo' => 10,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Seguridad',
                'subcategoria' => 'Candados',
                'proveedor_id' => 7,
                'nombre' => 'Candado Kryptonite Evolution',
                'descripcion' => 'Candado en U de acero endurecido',
                'precio_compra' => 35000,
                'precio_venta' => 55000,
                'stock_actual' => 18,
                'stock_minimo' => 8,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Seguridad',
                'subcategoria' => 'Cascos',
                'proveedor_id' => 7,
                'nombre' => 'Casco Giro Synthe MIPS',
                'descripcion' => 'Casco de carretera con tecnología MIPS',
                'precio_compra' => 85000,
                'precio_venta' => 120000,
                'stock_actual' => 15,
                'stock_minimo' => 8,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Ropa deportiva',
                'subcategoria' => 'Jerseys',
                'proveedor_id' => 8,
                'nombre' => 'Jersey Castelli Free Aero',
                'descripcion' => 'Jersey de ciclismo de carretera con bolsillos',
                'precio_compra' => 45000,
                'precio_venta' => 75000,
                'stock_actual' => 25,
                'stock_minimo' => 12,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Ropa deportiva',
                'subcategoria' => 'Culotes / Shorts',
                'proveedor_id' => 8,
                'nombre' => 'Culote Pearl Izumi Quest',
                'descripcion' => 'Culote con badana para ciclismo de larga distancia',
                'precio_compra' => 35000,
                'precio_venta' => 60000,
                'stock_actual' => 30,
                'stock_minimo' => 15,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Herramientas',
                'subcategoria' => 'Multiherramientas',
                'proveedor_id' => 7,
                'nombre' => 'Kit de Herramientas Park Tool',
                'descripcion' => 'Kit completo de herramientas para mantenimiento',
                'precio_compra' => 85000,
                'precio_venta' => 130000,
                'stock_actual' => 6,
                'stock_minimo' => 5,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Herramientas',
                'subcategoria' => 'Llaves y extractores',
                'proveedor_id' => 7,
                'nombre' => 'Bomba de Aire Topeak Joe Blow',
                'descripcion' => 'Bomba de piso con manómetro digital',
                'precio_compra' => 25000,
                'precio_venta' => 45000,
                'stock_actual' => 10,
                'stock_minimo' => 5,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Seguridad',
                'subcategoria' => 'Cascos',
                'proveedor_id' => 7,
                'nombre' => 'Casco MTB Fox Proframe',
                'descripcion' => 'Casco de enduro con protección facial',
                'precio_compra' => 120000,
                'precio_venta' => 180000,
                'stock_actual' => 8,
                'stock_minimo' => 5,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Seguridad',
                'subcategoria' => 'Luces',
                'proveedor_id' => 7,
                'nombre' => 'Reflectores LED traseros',
                'descripcion' => 'Set de 2 reflectores LED para la parte trasera',
                'precio_compra' => 8000,
                'precio_venta' => 15000,
                'stock_actual' => 40,
                'stock_minimo' => 20,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Nutrición',
                'subcategoria' => 'Geles',
                'proveedor_id' => 7,
                'nombre' => 'Geles Energéticos GU Energy',
                'descripcion' => 'Pack de 24 geles energéticos sabor fresa',
                'precio_compra' => 15000,
                'precio_venta' => 25000,
                'stock_actual' => 50,
                'stock_minimo' => 25,
                'estado' => 'activo',
            ],
            [
                'categoria_padre' => 'Nutrición',
                'subcategoria' => 'Bebidas',
                'proveedor_id' => 7,
                'nombre' => 'Bebida Isotónica Powerade',
                'descripcion' => 'Polvo para bebida isotónica sabor limón',
                'precio_compra' => 12000,
                'precio_venta' => 20000,
                'stock_actual' => 35,
                'stock_minimo' => 18,
                'estado' => 'activo',
            ],
        ];

        foreach ($products as $product) {
            $categoryId = $this->resolveSubcategoryId(
                $product['categoria_padre'],
                $product['subcategoria']
            );
            if ($categoryId === null) {
                $this->command?->warn(
                    'ProductsSeeder: no se encontró subcategoría «'.$product['subcategoria'].'» bajo «'.$product['categoria_padre'].'». Se omite «'.$product['nombre'].'».'
                );

                continue;
            }

            Product::create([
                'category_id' => $categoryId,
                'supplier_id' => $product['proveedor_id'],
                'name' => $product['nombre'],
                'description' => $product['descripcion'],
                'purchase_price' => $product['precio_compra'],
                'sale_price' => $product['precio_venta'],
                'stock_current' => $product['stock_actual'],
                'stock_minimum' => $product['stock_minimo'],
                'status' => 'active',
            ]);
        }
    }

    private function resolveSubcategoryId(string $parentName, string $subName): ?int
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
            ->value('category_id');
    }
}
