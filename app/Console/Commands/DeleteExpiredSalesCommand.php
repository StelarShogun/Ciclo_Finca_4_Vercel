<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use Carbon\Carbon;

class DeleteExpiredSalesCommand extends Command
{
    protected $signature = 'sales:delete-expired';
    protected $description = 'Elimina pedidos (ventas) que superaron el tiempo de vigencia configurado (order_expiry_days).';

    public function handle()
    {
        $days = Sale::getOrderExpirationDays();
        $limitDate = Carbon::now()->subDays($days);
        $query = Sale::where('sale_date', '<', $limitDate);
        $count = $query->count();
        if ($count === 0) {
            $this->info('No hay pedidos expirados para eliminar.');
            return self::SUCCESS;
        }
        $this->info("Eliminando {$count} pedido(s) con fecha anterior a " . $limitDate->format('Y-m-d H:i:s') . ".");
        $query->delete();
        $this->info('Pedidos expirados eliminados correctamente.');
        return self::SUCCESS;
    }
}
