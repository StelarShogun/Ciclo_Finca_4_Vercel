<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // Productos específicos del mundo del ciclismo
        $productos = [
            // BICICLETAS
            [
                'categoria_id' => 1, // Bicicletas
                'proveedor_id' => 1, // Trek Costa Rica
                'nombre' => 'Trek Fuel EX 8',
                'descripcion' => 'Bicicleta de montaña full suspension 29" con transmisión Shimano XT',
                'precio_compra' => 1200000,
                'precio_venta' => 1500000,
                'stock_actual' => 5,
                'stock_minimo' => 5,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 1, // Bicicletas
                'proveedor_id' => 2, // Specialized
                'nombre' => 'Specialized Tarmac SL7',
                'descripcion' => 'Bicicleta de ruta de carbono con grupo Shimano 105',
                'precio_compra' => 1800000,
                'precio_venta' => 2200000,
                'stock_actual' => 5,
                'stock_minimo' => 5,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 1, // Bicicletas
                'proveedor_id' => 5, // Giant
                'nombre' => 'Giant Escape 3',
                'descripcion' => 'Bicicleta urbana híbrida con frenos de disco',
                'precio_compra' => 180000,
                'precio_venta' => 250000,
                'stock_actual' => 8,
                'stock_minimo' => 5,
                'estado' => 'activo'
            ],
            
            // COMPONENTES
            [
                'categoria_id' => 2, // Componentes
                'proveedor_id' => 3, // Shimano
                'nombre' => 'Shimano Deore XT M8100',
                'descripcion' => 'Grupo de transmisión completo 12 velocidades',
                'precio_compra' => 180000,
                'precio_venta' => 250000,
                'stock_actual' => 5,
                'stock_minimo' => 5,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 2, // Componentes
                'proveedor_id' => 4, // SRAM
                'nombre' => 'SRAM GX Eagle',
                'descripcion' => 'Grupo de transmisión 12 velocidades para MTB',
                'precio_compra' => 220000,
                'precio_venta' => 300000,
                'stock_actual' => 5,
                'stock_minimo' => 5,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 2, // Componentes
                'proveedor_id' => 3, // Shimano
                'nombre' => 'Frenos de Disco Shimano BR-MT200',
                'descripcion' => 'Frenos de disco hidráulicos para MTB',
                'precio_compra' => 45000,
                'precio_venta' => 65000,
                'stock_actual' => 12,
                'stock_minimo' => 6,
                'estado' => 'activo'
            ],
            
            // ACCESORIOS
            [
                'categoria_id' => 3, // Accesorios
                'proveedor_id' => 7, // Accesorios Ciclismo Pro
                'nombre' => 'Casco Giro Synthe MIPS',
                'descripcion' => 'Casco de carretera con tecnología MIPS',
                'precio_compra' => 85000,
                'precio_venta' => 120000,
                'stock_actual' => 15,
                'stock_minimo' => 8,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 3, // Accesorios
                'proveedor_id' => 7, // Accesorios Ciclismo Pro
                'nombre' => 'Luces LED Cateye Volt 400',
                'descripcion' => 'Luz delantera LED recargable 400 lumens',
                'precio_compra' => 25000,
                'precio_venta' => 40000,
                'stock_actual' => 20,
                'stock_minimo' => 10,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 3, // Accesorios
                'proveedor_id' => 7, // Accesorios Ciclismo Pro
                'nombre' => 'Candado Kryptonite Evolution',
                'descripcion' => 'Candado en U de acero endurecido',
                'precio_compra' => 35000,
                'precio_venta' => 55000,
                'stock_actual' => 18,
                'stock_minimo' => 8,
                'estado' => 'activo'
            ],
            
            // ROPA DEPORTIVA
            [
                'categoria_id' => 4, // Ropa Deportiva
                'proveedor_id' => 8, // Ropa Deportiva Ciclismo
                'nombre' => 'Jersey Castelli Free Aero',
                'descripcion' => 'Jersey de ciclismo de carretera con bolsillos',
                'precio_compra' => 45000,
                'precio_venta' => 75000,
                'stock_actual' => 25,
                'stock_minimo' => 12,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 4, // Ropa Deportiva
                'proveedor_id' => 8, // Ropa Deportiva Ciclismo
                'nombre' => 'Culote Pearl Izumi Quest',
                'descripcion' => 'Culote con badana para ciclismo de larga distancia',
                'precio_compra' => 35000,
                'precio_venta' => 60000,
                'stock_actual' => 30,
                'stock_minimo' => 15,
                'estado' => 'activo'
            ],
            
            // HERRAMIENTAS
            [
                'categoria_id' => 5, // Herramientas
                'proveedor_id' => 7, // Accesorios Ciclismo Pro
                'nombre' => 'Kit de Herramientas Park Tool',
                'descripcion' => 'Kit completo de herramientas para mantenimiento',
                'precio_compra' => 85000,
                'precio_venta' => 130000,
                'stock_actual' => 6,
                'stock_minimo' => 5,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 5, // Herramientas
                'proveedor_id' => 7, // Accesorios Ciclismo Pro
                'nombre' => 'Bomba de Aire Topeak Joe Blow',
                'descripcion' => 'Bomba de piso con manómetro digital',
                'precio_compra' => 25000,
                'precio_venta' => 45000,
                'stock_actual' => 10,
                'stock_minimo' => 5,
                'estado' => 'activo'
            ],
            
            // SEGURIDAD
            [
                'categoria_id' => 6, // Seguridad
                'proveedor_id' => 7, // Accesorios Ciclismo Pro
                'nombre' => 'Casco MTB Fox Proframe',
                'descripcion' => 'Casco de enduro con protección facial',
                'precio_compra' => 120000,
                'precio_venta' => 180000,
                'stock_actual' => 8,
                'stock_minimo' => 5,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 6, // Seguridad
                'proveedor_id' => 7, // Accesorios Ciclismo Pro
                'nombre' => 'Reflectores LED traseros',
                'descripcion' => 'Set de 2 reflectores LED para la parte trasera',
                'precio_compra' => 8000,
                'precio_venta' => 15000,
                'stock_actual' => 40,
                'stock_minimo' => 20,
                'estado' => 'activo'
            ],
            
            // NUTRICIÓN
            [
                'categoria_id' => 7, // Nutrición
                'proveedor_id' => 7, // Accesorios Ciclismo Pro
                'nombre' => 'Geles Energéticos GU Energy',
                'descripcion' => 'Pack de 24 geles energéticos sabor fresa',
                'precio_compra' => 15000,
                'precio_venta' => 25000,
                'stock_actual' => 50,
                'stock_minimo' => 25,
                'estado' => 'activo'
            ],
            [
                'categoria_id' => 7, // Nutrición
                'proveedor_id' => 7, // Accesorios Ciclismo Pro
                'nombre' => 'Bebida Isotónica Powerade',
                'descripcion' => 'Polvo para bebida isotónica sabor limón',
                'precio_compra' => 12000,
                'precio_venta' => 20000,
                'stock_actual' => 35,
                'stock_minimo' => 18,
                'estado' => 'activo'
            ]
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}
