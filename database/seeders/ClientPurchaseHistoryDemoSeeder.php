<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * CF4-33 — ventas completadas ligadas a dos clientes distintos para probar el reporte
 * "Compras por cliente" (ids de usuario diferentes; no mezcla con demo CF4-24 de mostrador).
 */
class ClientPurchaseHistoryDemoSeeder extends Seeder
{
    private const NOTES = 'Demo CF4-33';

    public function run(): void
    {
        $admin = AdminUser::query()->orderBy('user_id')->first();
        if (! $admin) {
            $this->command?->warn('ClientPurchaseHistoryDemoSeeder: no hay admin; se omite.');

            return;
        }

        Sale::query()->where('notes', self::NOTES)->delete();

        $tz = config('app.timezone', 'America/Costa_Rica');
        $now = Carbon::now($tz);

        $clientA = Client::updateOrCreate(
            ['gmail' => 'cf33-demo-cliente-a@example.com'],
            [
                'name' => 'Demo',
                'first_surname' => 'Cliente Alfa',
                'second_surname' => null,
                'password' => Hash::make('DemoCF33A!'),
                'provider' => 'local',
            ]
        );

        $clientB = Client::updateOrCreate(
            ['gmail' => 'cf33-demo-cliente-b@example.com'],
            [
                'name' => 'Demo',
                'first_surname' => 'Cliente Beta',
                'second_surname' => null,
                'password' => Hash::make('DemoCF33B!'),
                'provider' => 'local',
            ]
        );

        $rows = [
            [$clientA->user_id, $now->copy()->subDays(3), 80_000.00, '001'],
            [$clientA->user_id, $now->copy()->subDays(8), 12_000.50, '002'],
            [$clientA->user_id, $now->copy()->subDays(18), 5_500.00, '003'],
            [$clientB->user_id, $now->copy()->subDays(2), 150_000.00, '004'],
            [$clientB->user_id, $now->copy()->subDays(12), 20_000.00, '005'],
        ];

        foreach ($rows as [$clientId, $date, $total, $suffix]) {
            Sale::create([
                'invoice_number' => 'INV-CF33-DEMO-'.$suffix,
                'client_id' => $clientId,
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
                'order_source' => 'web_cart',
            ]);
        }

        $this->command?->info(
            'ClientPurchaseHistoryDemoSeeder: '.count($rows).' ventas para clientes user_id '.$clientA->user_id.' y '.$clientB->user_id.'.',
        );
    }
}
