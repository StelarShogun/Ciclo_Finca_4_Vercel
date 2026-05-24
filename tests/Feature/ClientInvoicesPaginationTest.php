<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Sale;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-138 — Client invoices list pagination.
 */
class ClientInvoicesPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            foreach (['client_table', 'sales'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Tabla requerida no existe: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

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

            $page1 = $this->get(route('clients.invoices', [
                'tab' => 'facturas',
                'per_page' => 10,
            ]));

            $page1->assertOk();
            $page1->assertSee('cf4-pagination-toolbar', false);
            $page1->assertSee('Mostrando 1–10 de 12 resultados', false);
            $page1->assertSee('data-page="2"', false);

            $page2 = $this->get(route('clients.invoices', [
                'tab' => 'facturas',
                'per_page' => 10,
                'page' => 2,
            ]));

            $page2->assertOk();
            $page2->assertSee('Mostrando 11–12 de 12 resultados', false);
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

            $response = $this->get(route('clients.invoices', [
                'tab' => 'canceladas',
                'per_page' => 10,
            ]));

            $response->assertOk();
            $response->assertSee('Canceladas', false);
            $response->assertSee('cf4-pagination-toolbar', false);
            $response->assertSee('Mostrando 1–10 de 12 resultados', false);
            $response->assertSee('tab=canceladas', false);
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

            $response = $this->get(route('clients.invoices', [
                'tab' => 'historial',
                'per_page' => 10,
            ]));

            $response->assertOk();
            $response->assertSee('Historial de compras', false);
            $response->assertSee('tab=historial', false);
        } finally {
            $this->cleanupClientSales($client);
        }
    }
}
