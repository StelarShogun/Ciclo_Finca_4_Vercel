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
    }
}