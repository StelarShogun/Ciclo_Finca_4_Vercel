<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    // Run the database seeds.
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Trek Costa Rica',
                'primary_contact' => 'Roberto Jiménez',
                'phone' => '+506 2222-1001',
                'email' => 'ventas@trek.cr',
                'address' => 'San José, Costa Rica',
                'delivery_time' => 14,
                'rating' => 4.9,
                'status' => 'active'
            ],
            [
                'name' => 'Specialized Centroamérica',
                'primary_contact' => 'María Solís',
                'phone' => '+506 2222-1002',
                'email' => 'distribucion@specialized.cr',
                'address' => 'Cartago, Costa Rica',
                'delivery_time' => 21,
                'rating' => 4.8,
                'status' => 'active'
            ],
            [
                'name' => 'Shimano Costa Rica',
                'primary_contact' => 'Carlos Herrera',
                'phone' => '+506 2222-1003',
                'email' => 'componentes@shimano.cr',
                'address' => 'Alajuela, Costa Rica',
                'delivery_time' => 7,
                'rating' => 4.7,
                'status' => 'active'
            ],
            [
                'name' => 'SRAM Centroamérica',
                'primary_contact' => 'Ana Vega',
                'phone' => '+506 2222-1004',
                'email' => 'ventas@sram.cr',
                'address' => 'Heredia, Costa Rica',
                'delivery_time' => 10,
                'rating' => 4.6,
                'status' => 'active'
            ],
            [
                'name' => 'Giant Bicycles CR',
                'primary_contact' => 'Luis Morales',
                'phone' => '+506 2222-1005',
                'email' => 'distribucion@giant.cr',
                'address' => 'Puntarenas, Costa Rica',
                'delivery_time' => 18,
                'rating' => 4.5,
                'status' => 'active'
            ],
            [
                'name' => 'Cannondale Costa Rica',
                'primary_contact' => 'Patricia Rojas',
                'phone' => '+506 2222-1006',
                'email' => 'ventas@cannondale.cr',
                'address' => 'San José, Costa Rica',
                'delivery_time' => 16,
                'rating' => 4.8,
                'status' => 'active'
            ],
            [
                'name' => 'Accesorios Ciclismo Pro',
                'primary_contact' => 'Diego Fernández',
                'phone' => '+506 2222-1007',
                'email' => 'accesorios@ciclismo.cr',
                'address' => 'Cartago, Costa Rica',
                'delivery_time' => 5,
                'rating' => 4.9,
                'status' => 'active'
            ],
            [
                'name' => 'Ropa Deportiva Ciclismo',
                'primary_contact' => 'Sofía Ramírez',
                'phone' => '+506 2222-1008',
                'email' => 'ropa@ciclismo.cr',
                'address' => 'Alajuela, Costa Rica',
                'delivery_time' => 8,
                'rating' => 4.7,
                'status' => 'active'
            ]
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
