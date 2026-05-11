<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dataset demo completo: ejecuta los seeders estándar del proyecto y luego
 * — enlaza ventas completadas sin líneas con artículos del inventario (sale_items),
 * — crea ventas “FULL_DEMO_*” con varias líneas y totales coherentes.
 *
 * Recomendado sobre BD limpia:
 *   php artisan migrate:fresh --seed --class=FullDemoDatasetSeeder
 *
 * Sobre datos existentes: puede duplicar proveedores/pedidos si se corre SupplierSeeder u OrderSeeder
 * más de una vez; para repetir sin duplicar, usa migrate:fresh o ejecuta solo la parte final:
 *   php artisan db:seed --class=FullDemoDatasetSeeder
 * (los seeders internos con guardas —p. ej. ProductsSeeder— no duplican productos).
 */
class FullDemoDatasetSeeder extends Seeder
{
    private const NOTES_LINK_FIX = 'FULL_DEMO: líneas generadas para ventas sin detalle';

    private const NOTES_RICH_SALES = 'FULL_DEMO: venta multi-línea';

    public function run(): void
    {
        $this->ensureDemoBrandExists();

        $this->call([
            AdminSeeder::class,
            CategorySeeder::class,
            ClassificationCatalogSeeder::class,
            SupplierSeeder::class,
            ProductsSeeder::class,
            ProductClassificationDemoSeeder::class,
            ProductImagesSeeder::class,
            ClientUserSeeder::class,
        ]);

        if (! DB::table('orders')->exists()) {
            $this->call(OrderSeeder::class);
        } else {
            $this->command->warn('FullDemoDatasetSeeder: ya hay pedidos a proveedores (orders); OrderSeeder omitido.');
        }

        $this->call([
            SalesPerformanceDemoSeeder::class,
            ClientPurchaseHistoryDemoSeeder::class,
        ]);

        $this->attachSaleItemsToCompletedSalesMissingLines();
        $this->seedMultiLineConfirmedSales();

        $this->command->info('FullDemoDatasetSeeder: listo (inventario, pedidos proveedor si aplica, ventas con sale_items).');
    }

    private function ensureDemoBrandExists(): void
    {
        Brand::query()->firstOrCreate(
            ['name' => 'Marca demo Ciclo Finca'],
            []
        );
    }

    /**
     * Para cada venta completada sin sale_items: una línea que iguala el subtotal (demo consistente).
     */
    private function attachSaleItemsToCompletedSalesMissingLines(): void
    {
        $sales = Sale::query()
            ->where('status', 'completed')
            ->whereDoesntHave('saleItems')
            ->get();

        if ($sales->isEmpty()) {
            return;
        }

        $product = Product::query()
            ->where('status', 'active')
            ->orderBy('product_id')
            ->first();

        if (! $product instanceof Product) {
            $this->command->warn('FullDemoDatasetSeeder: no hay producto activo; no se crean sale_items.');

            return;
        }

        foreach ($sales as $sale) {
            $subtotal = (float) $sale->subtotal;
            if ($subtotal <= 0) {
                continue;
            }

            SaleItem::query()->create([
                'sale_id' => $sale->sale_id,
                'product_id' => $product->product_id,
                'quantity' => 1,
                'unit_price' => $subtotal,
                'unit_discount' => 0,
                'total' => $subtotal,
            ]);

            $note = trim((string) ($sale->notes ?? ''));
            if ($note === '' || ! Str::contains($note, self::NOTES_LINK_FIX)) {
                $suffix = $note !== '' ? $note.' | '.self::NOTES_LINK_FIX : self::NOTES_LINK_FIX;
                $sale->forceFill(['notes' => $suffix])->saveQuietly();
            }
        }

        $this->command->info('FullDemoDatasetSeeder: sale_items añadidos a '.$sales->count().' ventas completadas sin líneas.');
    }

    /**
     * Ventas nuevas con 2–3 líneas; subtotal/total = suma de líneas.
     */
    private function seedMultiLineConfirmedSales(): void
    {
        Sale::query()->where('notes', self::NOTES_RICH_SALES)->delete();

        $admin = AdminUser::query()->orderBy('user_id')->first();
        $client = Client::query()->orderBy('user_id')->first();
        $products = Product::query()
            ->where('status', 'active')
            ->where('stock_current', '>', 0)
            ->orderBy('product_id')
            ->take(8)
            ->get();

        if (! $admin || $products->count() < 3) {
            $this->command->warn('FullDemoDatasetSeeder: faltan admin o productos; se omiten ventas multi-línea.');

            return;
        }

        $tz = config('app.timezone', 'America/Costa_Rica');
        $now = Carbon::now($tz);

        $scenarios = [
            [
                'invoice' => 'INV-FULLDEMO-M1-'.Str::upper(Str::random(6)),
                'client_id' => $client?->user_id,
                'source' => 'walk_in',
                'lines' => [
                    ['idx' => 0, 'qty' => 1],
                    ['idx' => 1, 'qty' => 2],
                ],
            ],
            [
                'invoice' => 'INV-FULLDEMO-M2-'.Str::upper(Str::random(6)),
                'client_id' => $client?->user_id,
                'source' => 'web_cart',
                'lines' => [
                    ['idx' => 2, 'qty' => 1],
                    ['idx' => 3, 'qty' => 1],
                    ['idx' => 4, 'qty' => 3],
                ],
            ],
        ];

        foreach ($scenarios as $scenario) {
            $subtotal = 0.0;
            $lineModels = [];
            foreach ($scenario['lines'] as $line) {
                $p = $products[$line['idx']] ?? null;
                if (! $p instanceof Product) {
                    continue;
                }
                $unit = (float) $p->sale_price;
                $qty = (int) $line['qty'];
                $lineTotal = round($unit * $qty, 2);
                $subtotal += $lineTotal;
                $lineModels[] = compact('p', 'qty', 'unit', 'lineTotal');
            }

            if ($subtotal <= 0 || $lineModels === []) {
                continue;
            }

            $sale = Sale::create([
                'invoice_number' => $scenario['invoice'],
                'client_id' => $scenario['client_id'],
                'seller_admin_id' => $admin->user_id,
                'subtotal' => round($subtotal, 2),
                'iva' => 0,
                'discount' => 0,
                'total' => round($subtotal, 2),
                'payment_method' => 'cash',
                'payment_reference' => null,
                'status' => 'completed',
                'notes' => self::NOTES_RICH_SALES,
                'sale_date' => $now->copy()->subDays(rand(1, 14)),
                'buyer_name' => null,
                'buyer_email' => null,
                'order_source' => $scenario['source'],
            ]);

            foreach ($lineModels as $row) {
                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $row['p']->product_id,
                    'quantity' => $row['qty'],
                    'unit_price' => $row['unit'],
                    'unit_discount' => 0,
                    'total' => $row['lineTotal'],
                ]);
            }
        }

        $this->command->info('FullDemoDatasetSeeder: ventas multi-línea FULL_DEMO creadas.');
    }
}
