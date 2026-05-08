<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::query()->exists()) {
            $this->command->warn('ProductsSeeder: ya hay productos; se omite. Usa migrate:fresh --seed para repoblar desde cero.');

            return;
        }

        $supplierIds = Supplier::query()->pluck('supplier_id', 'name');

        $rows = [
            $this->row('Bicicletas', 'MTB', $supplierIds, 'Trek Costa Rica', 'Trek Fuel EX 8', 'MTB doble suspensión 29"', 1200000, 1500000, 6, 5, true),
            $this->row('Bicicletas', 'MTB', $supplierIds, 'Giant Bicycles CR', 'Giant Trance X 29 2', 'Trail 29" aluminio', 980000, 1250000, 18, 10, true),
            $this->row('Bicicletas', 'MTB', $supplierIds, 'Cannondale Costa Rica', 'Cannondale Habit 4', 'Enduro ligero 27.5"', 1100000, 1390000, 12, 8, false),
            $this->row('Bicicletas', 'Ruta / Gravel', $supplierIds, 'Specialized Centroamérica', 'Specialized Tarmac SL7', 'Carretera carbono, grupo 105', 1800000, 2200000, 5, 5, true),
            $this->row('Bicicletas', 'Ruta / Gravel', $supplierIds, 'Trek Costa Rica', 'Trek Domane AL 4', 'Endurance aluminio', 620000, 799000, 14, 10, false),
            $this->row('Bicicletas', 'Ruta / Gravel', $supplierIds, 'Giant Bicycles CR', 'Giant Revolt 2', 'Gravel aluminio', 720000, 920000, 9, 8, false),
            $this->row('Bicicletas', 'Urbana / Híbrida', $supplierIds, 'Giant Bicycles CR', 'Giant Escape 3', 'Urbana híbrida frenos disco', 180000, 250000, 20, 10, true),
            $this->row('Bicicletas', 'Urbana / Híbrida', $supplierIds, 'Specialized Centroamérica', 'Specialized Sirrus X 2.0', 'Fitness urbana', 320000, 410000, 11, 8, false),

            $this->row('Componentes', 'Transmisión', $supplierIds, 'Shimano Costa Rica', 'Shimano Deore XT M8100', 'Grupo 12v completo', 180000, 250000, 16, 10, false),
            $this->row('Componentes', 'Transmisión', $supplierIds, 'SRAM Centroamérica', 'SRAM GX Eagle', 'Grupo 12v MTB', 220000, 300000, 14, 10, true),
            $this->row('Componentes', 'Transmisión', $supplierIds, 'Shimano Costa Rica', 'Shimano 105 R7120', 'Grupo carretera 12v', 340000, 449000, 8, 8, false),
            $this->row('Componentes', 'Frenos', $supplierIds, 'Shimano Costa Rica', 'Shimano BR-MT200', 'Freno disco hidráulico MTB', 45000, 65000, 24, 12, false),
            $this->row('Componentes', 'Frenos', $supplierIds, 'SRAM Centroamérica', 'SRAM Level T', 'Freno hidráulico par', 52000, 72000, 19, 12, false),
            $this->row('Componentes', 'Ruedas y neumáticos', $supplierIds, 'Shimano Costa Rica', 'Rueda trasera Deore WH-MT500', 'Boost 12mm', 125000, 165000, 7, 6, false),
            $this->row('Componentes', 'Ruedas y neumáticos', $supplierIds, 'SRAM Centroamérica', 'Neumático Maxxis Minion DHF 29x2.5', 'Tubeless ready', 38000, 52000, 40, 20, false),

            $this->row('Accesorios', 'Iluminación', $supplierIds, 'Accesorios Ciclismo Pro', 'Cateye Volt 800', 'Luz delantera USB', 28000, 42000, 22, 12, false),
            $this->row('Accesorios', 'Iluminación', $supplierIds, 'Accesorios Ciclismo Pro', 'Led trasero Knog Blinder', 'USB recargable', 12000, 19000, 35, 18, false),
            $this->row('Accesorios', 'Portabultos', $supplierIds, 'Accesorios Ciclismo Pro', 'Portabultos Topeak Explorer', 'Aluminio 29"', 35000, 48000, 15, 10, false),
            $this->row('Accesorios', 'Hidratación', $supplierIds, 'Accesorios Ciclismo Pro', 'Bidón Elite Fly 750ml', 'Ligero', 4500, 8500, 60, 30, false),

            $this->row('Ropa deportiva', 'Jerseys', $supplierIds, 'Ropa Deportiva Ciclismo', 'Jersey Castelli Entrata', 'Manga corta', 28000, 48000, 26, 14, true),
            $this->row('Ropa deportiva', 'Jerseys', $supplierIds, 'Ropa Deportiva Ciclismo', 'Jersey Pearl Izumi Quest', 'Transpirable', 22000, 39000, 30, 15, false),
            $this->row('Ropa deportiva', 'Culotes / Shorts', $supplierIds, 'Ropa Deportiva Ciclismo', 'Culote Pearl Izumi Quest', 'Badana gel', 35000, 60000, 20, 15, false),
            $this->row('Ropa deportiva', 'Chaquetas', $supplierIds, 'Ropa Deportiva Ciclismo', 'Chubasquero Gore Shakedry', 'Ultraligero', 95000, 135000, 6, 6, false),

            $this->row('Herramientas', 'Multiherramientas', $supplierIds, 'Accesorios Ciclismo Pro', 'Park Tool IB-3', 'Mini multiherramienta', 18000, 28000, 28, 14, false),
            $this->row('Herramientas', 'Llaves y extractores', $supplierIds, 'Accesorios Ciclismo Pro', 'Llave dinamométrica Park TW-5', '2–14 Nm', 42000, 62000, 10, 8, false),
            $this->row('Herramientas', 'Multiherramientas', $supplierIds, 'Accesorios Ciclismo Pro', 'Kit Park Tool AK-5', 'Taller portátil', 85000, 130000, 5, 5, false),

            $this->row('Seguridad', 'Cascos', $supplierIds, 'Accesorios Ciclismo Pro', 'Casco Giro Synthe MIPS', 'Carretera', 85000, 120000, 14, 10, true),
            $this->row('Seguridad', 'Cascos', $supplierIds, 'Accesorios Ciclismo Pro', 'Casco Fox Proframe', 'Enduro integral', 120000, 180000, 8, 8, false),
            $this->row('Seguridad', 'Luces', $supplierIds, 'Accesorios Ciclismo Pro', 'Luces LED set delantero/trasero', 'Pilas incluidas', 8000, 15000, 45, 22, false),
            $this->row('Seguridad', 'Candados', $supplierIds, 'Accesorios Ciclismo Pro', 'Candado Kryptonite Evolution', 'U-lock + cable', 35000, 55000, 18, 12, false),

            $this->row('Nutrición', 'Geles', $supplierIds, 'Accesorios Ciclismo Pro', 'Geles GU Energy (caja 24)', 'Varios sabores', 15000, 25000, 50, 25, false),
            $this->row('Nutrición', 'Bebidas', $supplierIds, 'Accesorios Ciclismo Pro', 'Isotónico Powerade polvo', 'Limón 500g', 12000, 20000, 35, 18, false),
            $this->row('Nutrición', 'Barras', $supplierIds, 'Accesorios Ciclismo Pro', 'Barras Clif Bar (caja 12)', 'Energía', 18000, 28000, 42, 20, true),

            $this->row('Seguridad', 'Candados', $supplierIds, 'Accesorios Ciclismo Pro', 'Candado U demo agotado', 'Ejemplo sin stock en tienda', 15000, 22000, 0, 5, false, 'out_of_stock'),
        ];

        foreach ($rows as $data) {
            Product::create($data);
        }

        $this->command->info('ProductsSeeder: '.count($rows).' productos creados.');
    }

    /**
     * @param  Collection<string, int>  $supplierIds
     * @return array<string, mixed>
     */
    private function row(
        string $parentName,
        string $subName,
        $supplierIds,
        string $supplierName,
        string $name,
        string $description,
        int $purchase,
        int $sale,
        int $stockCurrent,
        int $stockMinimum,
        bool $isFeatured,
        string $status = 'active',
    ): array {
        $sid = $supplierIds->get($supplierName);
        if ($sid === null) {
            throw new \RuntimeException("Proveedor no encontrado en seeder: {$supplierName}");
        }

        return [
            'category_id' => $this->subcategoryId($parentName, $subName),
            'supplier_id' => (int) $sid,
            'name' => $name,
            'description' => $description,
            'purchase_price' => $purchase,
            'sale_price' => $sale,
            'stock_current' => $stockCurrent,
            'stock_minimum' => $stockMinimum,
            'status' => $status,
            'is_featured' => $isFeatured,
        ];
    }

    private function subcategoryId(string $parentName, string $subName): int
    {
        $parent = Category::query()
            ->where('name', $parentName)
            ->whereNull('parent_category_id')
            ->firstOrFail();

        $sub = Category::query()
            ->where('name', $subName)
            ->where('parent_category_id', $parent->category_id)
            ->firstOrFail();

        return (int) $sub->category_id;
    }
}
