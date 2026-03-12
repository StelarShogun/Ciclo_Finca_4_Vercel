<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Usuario;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SalesSeeder extends Seeder
{
    /**
     * Crea ventas de prueba con distintas fechas para probar listado y días restantes.
     */
    public function run(): void
    {
        $customers = Usuario::where('rol', 'cliente')->get();
        $products = Product::where('status', 'active')->where('stock_current', '>', 0)->get();
        $seller = Usuario::where('rol', 'admin')->first() ?? Usuario::first();

        if ($customers->isEmpty()) {
            $customer = Usuario::create([
                'nombre' => 'Cliente',
                'apellido' => 'Prueba',
                'email' => 'cliente.prueba@example.com',
                'password' => bcrypt('password'),
                'rol' => 'cliente',
            ]);
            $customers = collect([$customer]);
            $this->command->info('Cliente de prueba creado (cliente.prueba@example.com).');
        }
        if ($products->isEmpty()) {
            $this->command->warn('No hay productos activos con stock. Ejecuta ProductsSeeder y vuelve a ejecutar.');
            return;
        }
        if (!$seller) {
            $this->command->warn('No hay usuario vendedor (admin).');
            return;
        }

        $paymentMethods = ['cash', 'sinpe', 'transfer'];
        $statuses = ['pending', 'completed', 'completed', 'completed']; // más completadas para pruebas

        // Ventas con distintas fechas: hoy, ayer, 5 días, 10 días, 20 días, 28 días
        $dates = [
            now(),
            now()->subDay(),
            now()->subDays(5),
            now()->subDays(10),
            now()->subDays(20),
            now()->subDays(28),
        ];

        foreach ($dates as $saleDate) {
            $customer = $customers->random();
            $numItems = min(rand(1, 3), $products->count());
            $selectedProducts = $products->random($numItems);

            // Siguiente número por prefijo INV+Ymd para evitar duplicados al re-ejecutar
            $prefix = 'INV' . $saleDate->format('Ymd');
            $last = Sale::where('invoice_number', 'like', $prefix . '%')
                ->orderByRaw('LENGTH(invoice_number) DESC, invoice_number DESC')
                ->value('invoice_number');
            $nextSeq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
            $invoiceNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->usuario_id,
                'seller_id' => $seller->usuario_id,
                'subtotal' => 0,
                'iva' => 0,
                'discount' => 0,
                'total' => 0,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'payment_reference' => rand(0, 1) ? 'REF-' . rand(1000, 9999) : null,
                'status' => $statuses[array_rand($statuses)],
                'notes' => 'Venta de prueba (seeder)',
                'sale_date' => $saleDate,
            ]);

            $subtotal = 0;
            $itemsCollection = $selectedProducts instanceof \Illuminate\Support\Collection
                ? $selectedProducts
                : collect([$selectedProducts]);
            foreach ($itemsCollection as $product) {
                $quantity = min(rand(1, 2), $product->stock_current);
                if ($quantity < 1) {
                    continue;
                }
                $unitPrice = (float) $product->sale_price;
                $itemTotal = round($unitPrice * $quantity, 2);

                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit_discount' => 0,
                    'total' => $itemTotal,
                ]);

                $product->decrement('stock_current', $quantity);
                $subtotal += $itemTotal;
            }

            $iva = round($subtotal * 0.13, 2);
            $total = $subtotal + $iva;
            $sale->update([
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
            ]);
        }

        $this->command->info('Ventas de prueba creadas: ' . count($dates));
    }
}
