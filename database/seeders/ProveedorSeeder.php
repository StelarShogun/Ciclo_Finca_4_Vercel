<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proveedor;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            [
                'nombre' => 'Trek Costa Rica',
                'contacto_principal' => 'Roberto Jiménez',
                'telefono' => '+506 2222-1001',
                'correo_electronico' => 'ventas@trek.cr',
                'direccion' => 'San José, Costa Rica',
                'tiempo_entrega' => 14,
                'evaluacion' => 4.9,
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Specialized Centroamérica',
                'contacto_principal' => 'María Solís',
                'telefono' => '+506 2222-1002',
                'correo_electronico' => 'distribucion@specialized.cr',
                'direccion' => 'Cartago, Costa Rica',
                'tiempo_entrega' => 21,
                'evaluacion' => 4.8,
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Shimano Costa Rica',
                'contacto_principal' => 'Carlos Herrera',
                'telefono' => '+506 2222-1003',
                'correo_electronico' => 'componentes@shimano.cr',
                'direccion' => 'Alajuela, Costa Rica',
                'tiempo_entrega' => 7,
                'evaluacion' => 4.7,
                'estado' => 'activo'
            ],
            [
                'nombre' => 'SRAM Centroamérica',
                'contacto_principal' => 'Ana Vega',
                'telefono' => '+506 2222-1004',
                'correo_electronico' => 'ventas@sram.cr',
                'direccion' => 'Heredia, Costa Rica',
                'tiempo_entrega' => 10,
                'evaluacion' => 4.6,
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Giant Bicycles CR',
                'contacto_principal' => 'Luis Morales',
                'telefono' => '+506 2222-1005',
                'correo_electronico' => 'distribucion@giant.cr',
                'direccion' => 'Puntarenas, Costa Rica',
                'tiempo_entrega' => 18,
                'evaluacion' => 4.5,
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Cannondale Costa Rica',
                'contacto_principal' => 'Patricia Rojas',
                'telefono' => '+506 2222-1006',
                'correo_electronico' => 'ventas@cannondale.cr',
                'direccion' => 'San José, Costa Rica',
                'tiempo_entrega' => 16,
                'evaluacion' => 4.8,
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Accesorios Ciclismo Pro',
                'contacto_principal' => 'Diego Fernández',
                'telefono' => '+506 2222-1007',
                'correo_electronico' => 'accesorios@ciclismo.cr',
                'direccion' => 'Cartago, Costa Rica',
                'tiempo_entrega' => 5,
                'evaluacion' => 4.9,
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Ropa Deportiva Ciclismo',
                'contacto_principal' => 'Sofía Ramírez',
                'telefono' => '+506 2222-1008',
                'correo_electronico' => 'ropa@ciclismo.cr',
                'direccion' => 'Alajuela, Costa Rica',
                'tiempo_entrega' => 8,
                'evaluacion' => 4.7,
                'estado' => 'activo'
            ]
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::create($proveedor);
        }
    }
}