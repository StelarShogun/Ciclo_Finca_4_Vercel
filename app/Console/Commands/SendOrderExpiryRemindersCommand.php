<?php

namespace App\Console\Commands;

use App\Mail\OrderExpiryReminderMail;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendOrderExpiryRemindersCommand extends Command
{
    protected $signature = 'sales:send-expiry-reminders';

    protected $description = 'Envía recordatorio por correo a clientes con pedidos pendientes que vencen en menos de 24 horas.';

    public function handle(): int
    {
        $days = Sale::getOrderExpirationDays();

        // Orders expiring in the next 24 h:
        //   sale_date >= now - days        → not yet expired
        //   sale_date <  now - (days - 1)  → expires within the next day
        $expirationThreshold = now()->subDays($days);
        $reminderThreshold = now()->subDays($days - 1);

        $orders = Sale::query()
            ->where('status', 'pending')
            ->where('sale_date', '>=', $expirationThreshold)
            ->where('sale_date', '<', $reminderThreshold)
            ->where(fn ($q) => $q->where('order_source', 'web_cart')->orWhereNull('order_source'))
            ->with(['client', 'saleItems.product'])
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No hay pedidos pendientes que venzan en las próximas 24 horas.');

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            $email = $order->client_id
                ? $order->client->gmail
                : $order->buyer_email;

            if (! $email) {
                $this->warn("Pedido #{$order->sale_id}: sin correo registrado, omitido.");
                $skipped++;

                continue;
            }

            $clientName = $order->client
                ? trim("{$order->client->name} {$order->client->first_surname}")
                : ($order->buyer_name ?? 'Cliente');

            Mail::to($email)->send(
                new OrderExpiryReminderMail($order, $order->expires_at, $clientName)
            );

            $this->info("Recordatorio enviado → {$email} (Pedido #{$order->sale_id}, vence {$order->expires_at->format('d/m/Y')}).");
            $sent++;
        }

        $this->info("Finalizado: {$sent} recordatorio(s) enviado(s), {$skipped} omitido(s) por falta de correo.");

        return self::SUCCESS;
    }
}
