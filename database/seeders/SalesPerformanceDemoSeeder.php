<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Ventas completadas en la semana actual y en la anterior (misma lógica que el reporte CF4-24).
 * Sirve para probar totales y comparativa en "Esta semana".
 */
class SalesPerformanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = AdminUser::query()->orderBy('user_id')->first();
        if (! $admin) {
            $this->command?->warn('SalesPerformanceDemoSeeder: no hay admin; se omite.');

            return;
        }

        $tz = config('app.timezone', 'America/Costa_Rica');
        $now = Carbon::now($tz);
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $prevWeekStart = $weekStart->copy()->subWeek();

        $rows = [
            [$weekStart->copy()->addDay(), 45_000.00],
            [$weekStart->copy()->addDays(2)->setTime(11, 30), 12_500.50],
            [$weekStart->copy()->addDays(4), 8_200.00],
            [$prevWeekStart->copy()->addDay(), 30_000.00],
            [$prevWeekStart->copy()->addDays(3), 55_750.00],
        ];

        foreach ($rows as $i => [$date, $total]) {
            Sale::create([
                'invoice_number' => 'INV-DEMO-'.$now->format('Ymd').'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'customer_id' => null,
                'client_id' => null,
                'seller_id' => null,
                'seller_admin_id' => $admin->user_id,
                'subtotal' => $total,
                'iva' => 0,
                'discount' => 0,
                'total' => $total,
                'payment_method' => 'cash',
                'payment_reference' => null,
                'status' => 'completed',
                'notes' => 'Demo CF4-24',
                'sale_date' => $date,
                'buyer_name' => null,
                'buyer_email' => null,
                'order_source' => 'walk_in',
            ]);
        }

        $this->command?->info('SalesPerformanceDemoSeeder: '.count($rows).' ventas completadas (esta semana + anterior).');
    }
}
