<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Ventas completadas de demo para CF4-24:
 * - Semana actual y anterior (útil en "Esta semana").
 * - Fechas ancladas al año calendario actual y al anterior (útil en "Este año" y otros presets amplios).
 */
class SalesPerformanceDemoSeeder extends Seeder
{
    private const NOTES = 'Demo CF4-24';

    public function run(): void
    {
        $admin = AdminUser::query()->orderBy('user_id')->first();
        if (! $admin) {
            $this->command?->warn('SalesPerformanceDemoSeeder: no hay admin; se omite.');

            return;
        }

        Sale::query()->where('notes', self::NOTES)->delete();

        $tz = config('app.timezone', 'America/Costa_Rica');
        $now = Carbon::now($tz);
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $prevWeekStart = $weekStart->copy()->subWeek();
        $y = (int) $now->year;

        $rows = [
            [$weekStart->copy()->addDay(), 45_000.00],
            [$weekStart->copy()->addDays(2)->setTime(11, 30), 12_500.50],
            [$weekStart->copy()->addDays(4), 8_200.00],
            [$prevWeekStart->copy()->addDay(), 30_000.00],
            [$prevWeekStart->copy()->addDays(3), 55_750.00],
            [Carbon::create($y, 1, 15, 10, 0, 0, $tz), 25_000.00],
            [Carbon::create($y, 8, 1, 14, 0, 0, $tz), 18_500.00],
            [Carbon::create($y - 1, 4, 20, 9, 0, 0, $tz), 40_000.00],
            [Carbon::create($y - 1, 11, 5, 16, 0, 0, $tz), 22_000.00],
        ];

        foreach ($rows as $i => [$date, $total]) {
            Sale::create([
                'invoice_number' => 'INV-DEMO-'.$now->format('Ymd').'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'client_id' => null,
                'seller_admin_id' => $admin->user_id,
                'subtotal' => $total,
                'iva' => 0,
                'discount' => 0,
                'total' => $total,
                'payment_method' => 'cash',
                'payment_reference' => null,
                'status' => 'completed',
                'notes' => self::NOTES,
                'sale_date' => $date,
                'buyer_name' => null,
                'buyer_email' => null,
                'order_source' => 'walk_in',
            ]);
        }

        $this->command?->info('SalesPerformanceDemoSeeder: '.count($rows).' ventas completadas (semana + año actual/anterior).');
    }
}
