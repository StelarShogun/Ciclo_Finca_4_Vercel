<?php

namespace Database\Seeders;

use App\Models\OrderItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    private int $seq = 0;

    private function nextPo(): string
    {
        $this->seq++;
        return 'PO-' . now()->format('Y') . '-' . str_pad((string) $this->seq, 4, '0', STR_PAD_LEFT);
    }

    public function run(): void
    {
        $suppliers = Supplier::with(['products' => function ($q) {
            $q->where('status', 'active');
        }])->where('status', 'active')->get();

        $states = ['pending', 'confirmed', 'delivered', 'cancelled'];

        foreach ($suppliers as $supplier) {
            $products = $supplier->products;

            if ($products->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < 3; $i++) {
                $pickCount = min(rand(1, 3), $products->count());
                $selected = $products->random($pickCount);

                $orderTotal = 0;
                $lines = [];

                foreach ($selected as $product) {
                    $quantity = rand(1, 5);
                    $unitPrice = (float) $product->purchase_price;
                    $lineTotal = round($quantity * $unitPrice, 2);
                    $orderTotal += $lineTotal;

                    $lines[] = [
                        'product_id' => $product->product_id,
                        'name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $lineTotal,
                    ];
                }

                $state = $states[array_rand($states)];
                $orderDate = Carbon::now()->subDays(rand(0, 120));

                $row = [
                    'supplier_id' => $supplier->supplier_id,
                    'date' => in_array($state, ['confirmed', 'delivered']) ? $orderDate : null,
                    'state' => $state,
                    'total' => round($orderTotal, 2),
                    'po_number' => $this->nextPo(),
                    'estimated_delivery_date' => $orderDate->copy()->addDays(rand(3, 14))->toDateString(),
                    'delivered_at' => $state === 'delivered' ? $orderDate->copy()->addDays(rand(3, 14)) : null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                $numOrder = (int) DB::table('orders')->insertGetId($row);

                foreach ($lines as $line) {
                    OrderItem::create([
                        'order_num_order' => $numOrder,
                        'product_id' => $line['product_id'],
                        'name' => $line['name'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'total' => $line['total'],
                    ]);
                }
            }
        }
    }
}
