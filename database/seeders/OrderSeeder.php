<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Load all active suppliers with their related products
        $suppliers = Supplier::with(['products' => function ($q) {
            $q->where('status', 'active');
        }])->where('status', 'active')->get();

        $states = ['pending', 'confirmed', 'delivered', 'cancelled'];

        // 3 orders per supplier, each with products that belong to that supplier
        foreach ($suppliers as $supplier) {
            $products = $supplier->products;

            if ($products->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < 3; $i++) {
                // Pick 1 to 3 products from this supplier (no more than available)
                $pickCount = min(rand(1, 3), $products->count());
                $selected = $products->random($pickCount);

                $items = [];
                $orderTotal = 0;

                foreach ($selected as $product) {
                    $quantity  = rand(1, 5);
                    $unitPrice = (float) $product->purchase_price;
                    $lineTotal = round($quantity * $unitPrice, 2);

                    $items[] = [
                        'product_id' => $product->product_id,
                        'name'       => $product->name,
                        'quantity'   => $quantity,
                        'unit_price' => $unitPrice,
                        'total'      => $lineTotal,
                    ];

                    $orderTotal += $lineTotal;
                }

                DB::table('orders')->insert([
                    'supplier_id' => $supplier->supplier_id,
                    'products'    => json_encode($items),
                    'date'        => Carbon::now()->subDays(rand(0, 120)),
                    'state'       => $states[array_rand($states)],
                    'total'       => round($orderTotal, 2),
                    'created_at'  => Carbon::now(),
                    'updated_at'  => Carbon::now(),
                ]);
            }
        }
    }
}
