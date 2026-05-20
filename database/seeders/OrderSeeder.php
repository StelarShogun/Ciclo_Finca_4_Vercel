<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\OrderItem;
use App\Models\OrderStateTimeline;
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

        return 'PO-'.now()->format('Y').'-'.str_pad((string) $this->seq, 4, '0', STR_PAD_LEFT);
    }

    public function run(): void
    {
        if (DB::table('orders')->exists()) {
            $this->command?->warn(
                'OrderSeeder: ya hay pedidos; se omite. Usa migrate:fresh --seed o db:setup para repoblar desde cero.'
            );

            return;
        }

        $adminId = AdminUser::query()->value('user_id');

        if ($adminId === null) {
            $this->command?->warn('OrderSeeder: no admin user found — timeline rows will use user_id = 1.');
            $adminId = 1;
        }

        $suppliers = Supplier::with(['products' => function ($q) {
            $q->where('status', 'active');
        }])->where('status', 'active')->get();

        // -------------------------------------------------------------------
        // 1) Seed genérico para TODOS los proveedores (igual que antes,
        //    pero estimated_delivery_date = null para alinear con la HU:
        //    el campo solo se calcula al confirmar, ya no se hardcodea).
        // -------------------------------------------------------------------
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
                    // HU: la fecha estimada ya no se setea al crear/seedear.
                    // El cálculo automático ocurre al confirmar.
                    'estimated_delivery_date' => null,
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

        // -------------------------------------------------------------------
        // 2) Escenario controlado para "Trek Costa Rica":
        //    - 4 pedidos delivered con timeline confirmed→delivered de
        //      5, 7, 9 y 11 días → promedio = 8 días.
        //    - 3 pedidos draft listos para que el admin los confirme y vea
        //      que la fecha estimada se calcula como hoy + 8 días.
        //    - 5 pedidos pending (estado legacy, conservado para
        //      compatibilidad histórica). pending → confirmed sigue siendo
        //      una transición válida, así que también sirven para probar
        //      el cálculo automático al confirmar.
        // -------------------------------------------------------------------
        $trek = $suppliers->firstWhere('name', 'Trek Costa Rica');

        if (! $trek || $trek->products->isEmpty()) {
            $this->command?->warn('OrderSeeder: Trek Costa Rica no encontrado o sin productos activos — saltando escenario de prueba.');

            return;
        }

        $this->seedTrekDeliveredHistory(
            supplier: $trek,
            adminId: (int) $adminId,
            daysPattern: [5, 7, 9, 11], // promedio = 8
        );

        $this->seedTrekDraftOrders(
            supplier: $trek,
            adminId: (int) $adminId,
            count: 3,
        );

        $this->seedTrekPendingOrders(
            supplier: $trek,
            adminId: (int) $adminId,
            count: 5,
        );

        $this->command?->info('OrderSeeder: Trek -> 4 delivered (5/7/9/11 días, avg 8) + 3 draft + 5 pending creados.');
    }

    /**
     * Crea N pedidos en estado "delivered" para Trek, cada uno con su entry
     * en order_state_timeline (confirmed + delivered) separada por $daysPattern[i] días.
     *
     * Esto es lo que SupplierDeliveryEstimator lee para calcular el promedio.
     */
    private function seedTrekDeliveredHistory(Supplier $supplier, int $adminId, array $daysPattern): void
    {
        // Anclamos los pedidos hace 6 meses para que no choquen con la fecha actual.
        $anchor = Carbon::now()->subMonths(6)->startOfDay();

        foreach ($daysPattern as $offset => $daysBetween) {
            // Cada pedido empieza 10 días después del anterior para que el orden
            // cronológico sea limpio en el listado.
            $confirmedAt = $anchor->copy()->addDays($offset * 10);
            $deliveredAt = $confirmedAt->copy()->addDays($daysBetween);

            $product = $supplier->products->random();
            $quantity = rand(2, 5);
            $unitPrice = (float) $product->purchase_price;
            $total = round($quantity * $unitPrice, 2);

            $row = [
                'supplier_id' => $supplier->supplier_id,
                'date' => $confirmedAt,
                'state' => 'delivered',
                'total' => $total,
                'po_number' => $this->nextPo(),
                'estimated_delivery_date' => null,
                'received_at' => $deliveredAt,
                'delivered_at' => $deliveredAt,
                'created_at' => $confirmedAt,
                'updated_at' => $deliveredAt,
            ];

            $numOrder = (int) DB::table('orders')->insertGetId($row);

            OrderItem::create([
                'order_num_order' => $numOrder,
                'product_id' => $product->product_id,
                'name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $total,
                'received_quantity' => $quantity,
            ]);

            // El timeline es la fuente de verdad del estimator.
            OrderStateTimeline::create([
                'num_order' => $numOrder,
                'user_id' => $adminId,
                'state' => 'draft',
                'changed_at' => $confirmedAt->copy()->subMinutes(5),
            ]);

            OrderStateTimeline::create([
                'num_order' => $numOrder,
                'user_id' => $adminId,
                'state' => 'confirmed',
                'changed_at' => $confirmedAt,
            ]);

            OrderStateTimeline::create([
                'num_order' => $numOrder,
                'user_id' => $adminId,
                'state' => 'delivered',
                'changed_at' => $deliveredAt,
            ]);
        }
    }

    /**
     * Crea N pedidos en estado "draft" para Trek, sin timeline más allá del propio
     * draft. Estos son los pedidos que el admin va a confirmar manualmente para
     * verificar que la fecha estimada se calcula como hoy + promedio (= hoy + 8).
     */
    private function seedTrekDraftOrders(Supplier $supplier, int $adminId, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $createdAt = Carbon::now()->subHours($i + 1);
            $numOrder = $this->insertTrekOrder($supplier, state: 'draft', createdAt: $createdAt);

            OrderStateTimeline::create([
                'num_order' => $numOrder,
                'user_id' => $adminId,
                'state' => 'draft',
                'changed_at' => $createdAt,
            ]);
        }
    }

    /**
     * Crea N pedidos en estado "pending" (legacy) para Trek.
     *
     * "pending" se conserva en Order::TRANSITIONS solo como origen para
     * compatibilidad con pedidos históricos: pending → confirmed sigue siendo
     * una transición válida, así que estos pedidos también permiten probar
     * el cálculo automático de la fecha estimada al confirmarlos.
     */
    private function seedTrekPendingOrders(Supplier $supplier, int $adminId, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            // Pendientes se anclan unos días atrás para que no se confundan
            // con los draft recién creados en el listado.
            $createdAt = Carbon::now()->subDays($i + 1);
            $numOrder = $this->insertTrekOrder($supplier, state: 'pending', createdAt: $createdAt);

            OrderStateTimeline::create([
                'num_order' => $numOrder,
                'user_id' => $adminId,
                'state' => 'pending',
                'changed_at' => $createdAt,
            ]);
        }
    }

    /**
     * Inserta un pedido de Trek con líneas pero sin timeline (el caller decide
     * cómo poblarlo). Devuelve el num_order para encadenar inserciones.
     */
    private function insertTrekOrder(Supplier $supplier, string $state, Carbon $createdAt): int
    {
        $pickCount = min(rand(1, 3), $supplier->products->count());
        $selected = $supplier->products->random($pickCount);

        $orderTotal = 0;
        $lines = [];

        foreach ($selected as $product) {
            $quantity = rand(1, 4);
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

        $row = [
            'supplier_id' => $supplier->supplier_id,
            // En este sistema 'date' solo se setea desde confirmed en adelante;
            // draft y pending lo dejan en null igual que el flujo real.
            'date' => null,
            'state' => $state,
            'total' => round($orderTotal, 2),
            'po_number' => $this->nextPo(),
            'estimated_delivery_date' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
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

        return $numOrder;
    }
}
