<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Sale;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * CF4-138 — Client invoices list pagination.
 */
class ClientInvoicesPaginationTest extends TestCase
{
    private function createIsolatedClient(): Client
    {
        return Client::create([
            'name' => 'Invoice',
            'first_surname' => 'Pager',
            'second_surname' => null,
            'gmail' => 'invoice-pager-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
    }

    /**
     * @return list<int>
     */
    private function seedPendingSales(Client $client, int $count): array
    {
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $sale = Sale::create([
                'invoice_number' => 'INV-PG-'.uniqid('', true),
                'client_id' => $client->user_id,
                'sale_date' => now()->subMinutes($count - $i),
                'payment_method' => 'cash',
                'status' => 'pending',
                'subtotal' => 1000,
                'iva' => 0,
                'discount' => 0,
                'total' => 1000,
                'order_source' => 'web_cart',
                'notes' => null,
            ]);
            $ids[] = (int) $sale->sale_id;
        }

        return $ids;
    }

    private function cleanupClientSales(Client $client): void
    {
        Sale::query()->where('client_id', $client->user_id)->delete();
        $client->delete();
    }

    public function test_invoices_facturas_tab_paginates_with_shared_component(): void
    {
        $client = $this->createIsolatedClient();

        try {
            $this->seedPendingSales($client, 12);
            $this->actingAs($client, 'clients');

            $this->get(route('clients.invoices', [
                'tab' => 'facturas',
                'per_page' => 10,
            ]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Client/Invoices/Index', false)
                    ->where('tab', 'facturas')
                    ->where('pagination.total', 12)
                    ->where('pagination.from', 1)
                    ->where('pagination.to', 10)
                    ->where('pagination.lastPage', 2)
                    ->where('pagination.links', fn ($links) => collect($links)->contains(
                        fn ($link) => str_contains((string) ($link['url'] ?? ''), 'page=2')
                    ))
                );

            $this->get(route('clients.invoices', [
                'tab' => 'facturas',
                'per_page' => 10,
                'page' => 2,
            ]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Client/Invoices/Index', false)
                    ->where('pagination.from', 11)
                    ->where('pagination.to', 12)
                );
        } finally {
            $this->cleanupClientSales($client);
        }
    }

    public function test_invoices_canceladas_tab_paginates_with_shared_component(): void
    {
        $client = $this->createIsolatedClient();

        try {
            for ($i = 0; $i < 12; $i++) {
                Sale::create([
                    'invoice_number' => 'INV-CAN-'.uniqid('', true),
                    'client_id' => $client->user_id,
                    'sale_date' => now()->subMinutes(12 - $i),
                    'payment_method' => 'cash',
                    'status' => 'cancelled',
                    'subtotal' => 1000,
                    'iva' => 0,
                    'discount' => 0,
                    'total' => 1000,
                    'order_source' => 'web_cart',
                    'notes' => null,
                ]);
            }

            $this->actingAs($client, 'clients');

            $this->get(route('clients.invoices', [
                'tab' => 'canceladas',
                'per_page' => 10,
            ]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Client/Invoices/Index', false)
                    ->where('tab', 'canceladas')
                    ->where('pagination.total', 12)
                    ->where('pagination.from', 1)
                    ->where('pagination.to', 10)
                    ->where('pagination.links', fn ($links) => collect($links)->contains(
                        fn ($link) => str_contains((string) ($link['url'] ?? ''), 'tab=canceladas')
                    ))
                );
        } finally {
            $this->cleanupClientSales($client);
        }
    }

    public function test_invoices_tab_query_is_preserved_in_pagination_links(): void
    {
        $client = $this->createIsolatedClient();

        try {
            for ($i = 0; $i < 12; $i++) {
                Sale::create([
                    'invoice_number' => 'INV-HIST-'.uniqid('', true),
                    'client_id' => $client->user_id,
                    'sale_date' => now()->subMinutes(12 - $i),
                    'payment_method' => 'cash',
                    'status' => 'completed',
                    'subtotal' => 1000,
                    'iva' => 0,
                    'discount' => 0,
                    'total' => 1000,
                    'order_source' => 'web_cart',
                    'notes' => null,
                ]);
            }

            $this->actingAs($client, 'clients');

            $this->get(route('clients.invoices', [
                'tab' => 'historial',
                'per_page' => 10,
            ]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Client/Invoices/Index', false)
                    ->where('tab', 'historial')
                    ->where('pagination.total', 12)
                    ->where('pagination.links', fn ($links) => collect($links)->contains(
                        fn ($link) => str_contains((string) ($link['url'] ?? ''), 'tab=historial')
                    ))
                );
        } finally {
            $this->cleanupClientSales($client);
        }
    }
}
