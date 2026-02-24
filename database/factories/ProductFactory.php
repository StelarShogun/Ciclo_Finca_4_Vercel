<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $precioCompra = $this->faker->randomFloat(2, 500, 50000);
        $precioVenta  = $precioCompra * $this->faker->randomFloat(2, 1.1, 1.5);
        $stockMin     = $this->faker->numberBetween(5, 15);
        $stockAct     = $this->faker->numberBetween($stockMin, $stockMin + 50);

        return [
            'category_id'  => Category::inRandomOrder()->first()->category_id,
            'supplier_id'  => Supplier::inRandomOrder()->first()->supplier_id,
            'name'        => $this->faker->words(3, true),
            'description'   => $this->faker->sentence(),
            'purchase_price' => $precioCompra,
            'sale_price'  => $precioVenta,
            'stock_current'  => $stockAct,
            'stock_minimum'  => $stockMin,
            'status'        => 'active',
        ];
    }
}
