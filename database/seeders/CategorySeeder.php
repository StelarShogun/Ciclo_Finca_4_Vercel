<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    // Run the database seeds.
    public function run(): void
    {
        $categories = [
            // Main cycling categories
            ['name' => 'Bicicletas', 'description' => 'Bicicletas completas de diferentes tipos', 'parent_category_id' => null],
            ['name' => 'Componentes', 'description' => 'Componentes y repuestos para bicicleta', 'parent_category_id' => null],
            ['name' => 'Accesorios', 'description' => 'Accesorios y equipamiento para ciclismo', 'parent_category_id' => null],
            ['name' => 'Ropa deportiva', 'description' => 'Vestimenta especializada para ciclismo', 'parent_category_id' => null],
            ['name' => 'Herramientas', 'description' => 'Herramientas para mantenimiento de bicicletas', 'parent_category_id' => null],
            ['name' => 'Seguridad', 'description' => 'Cascos, luces y equipo de seguridad', 'parent_category_id' => null],
            ['name' => 'Nutrición', 'description' => 'Suplementos y bebidas deportivas', 'parent_category_id' => null],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $parents = Category::whereNull('parent_category_id')->get()->keyBy('name');

        $subcategories = [
            'Bicicletas' => ['MTB', 'Ruta / Gravel', 'Urbana / Híbrida'],
            'Componentes' => ['Transmisión', 'Frenos', 'Ruedas y neumáticos'],
            'Accesorios' => ['Iluminación', 'Portabultos', 'Hidratación'],
            'Ropa deportiva' => ['Jerseys', 'Culotes / Shorts', 'Chaquetas'],
            'Herramientas' => ['Multiherramientas', 'Llaves y extractores'],
            'Seguridad' => ['Cascos', 'Luces', 'Candados'],
            'Nutrición' => ['Geles', 'Bebidas', 'Barras'],
        ];

        foreach ($subcategories as $parentName => $children) {
            $parent = $parents->get($parentName);
            if (!$parent) {
                continue;
            }
            foreach ($children as $childName) {
                Category::firstOrCreate(
                    [
                        'name' => $childName,
                        'parent_category_id' => $parent->category_id,
                    ],
                    [
                        'description' => 'Subcategoría de ' . $parentName,
                    ]
                );
            }
        }
    }
}