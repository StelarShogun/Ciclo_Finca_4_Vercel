<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            // Categorías principales del ciclismo
            ['nombre' => 'Bicicletas', 'descripcion' => 'Bicicletas completas de diferentes tipos', 'categoria_padre_id' => null],
            ['nombre' => 'Componentes', 'descripcion' => 'Componentes y repuestos para bicicletas', 'categoria_padre_id' => null],
            ['nombre' => 'Accesorios', 'descripcion' => 'Accesorios y equipamiento para ciclismo', 'categoria_padre_id' => null],
            ['nombre' => 'Ropa Deportiva', 'descripcion' => 'Ropa especializada para ciclismo', 'categoria_padre_id' => null],
            ['nombre' => 'Herramientas', 'descripcion' => 'Herramientas para mantenimiento de bicicletas', 'categoria_padre_id' => null],
            ['nombre' => 'Seguridad', 'descripcion' => 'Cascos, luces y elementos de seguridad', 'categoria_padre_id' => null],
            ['nombre' => 'Nutrición', 'descripcion' => 'Suplementos y bebidas deportivas', 'categoria_padre_id' => null],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}