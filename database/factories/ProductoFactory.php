<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Proveedor;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        $precioCompra = $this->faker->randomFloat(2, 500, 50000);
        $precioVenta  = $precioCompra * $this->faker->randomFloat(2, 1.1, 1.5);
        $stockMin     = $this->faker->numberBetween(5, 15);
        $stockAct     = $this->faker->numberBetween($stockMin, $stockMin + 50);

        return [
            'categoria_id'  => Categoria::inRandomOrder()->first()->categoria_id,
            'proveedor_id'  => Proveedor::inRandomOrder()->first()->proveedor_id,
            'nombre'        => $this->faker->words(3, true),
            'descripcion'   => $this->faker->sentence(),
            'precio_compra' => $precioCompra,
            'precio_venta'  => $precioVenta,
            'stock_actual'  => $stockAct,
            'stock_minimo'  => $stockMin,
            'estado'        => 'activo',
        ];
    }
}
