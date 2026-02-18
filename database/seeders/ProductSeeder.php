<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Proveedor;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = Categoria::all();
        $proveedores = Proveedor::all();

        if ($categorias->isEmpty() || $proveedores->isEmpty()) {
            $this->command->info('No categories or providers found, please seed them first.');
            return;
        }

        for ($i = 0; $i < 30; $i++) {
            Producto::create([
                'nombre' => 'Producto ' . ($i + 1),
                'descripcion' => 'Descripción del producto ' . ($i + 1),
                'precio_compra' => rand(1000, 5000),
                'precio_venta' => rand(5000, 10000),
                'stock_actual' => rand(0, 100),
                'stock_minimo' => rand(5, 20),
                'categoria_id' => $categorias->random()->categoria_id,
                'proveedor_id' => $proveedores->random()->proveedor_id,
                'estado' => 'activo',
            ]);
        }
    }
}
