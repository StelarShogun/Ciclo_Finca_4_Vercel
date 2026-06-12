<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * CF4-89 — Client web order detail.
 *
 * | ID       | Caso de prueba                                                | Tipo        | Automatizado en                                       |
 * |----------|---------------------------------------------------------------|-------------|-------------------------------------------------------|
 * | CP89-01  | Cliente autenticado ve detalle de su propio pedido             | Funcional   | test_authenticated_owner_sees_own_invoice_detail      |
 * | CP89-02  | Cliente autenticado obtiene 404 al pedir un pedido ajeno       | Seguridad   | test_authenticated_client_gets_404_on_foreign_invoice |
 * | CP89-03  | Invitado es redirigido al login al intentar ver el detalle     | Seguridad   | test_guest_is_redirected_to_login                     |
 */
class ClientInvoiceDetailTest extends TestCase
{
    use RefreshDatabase;

    private function createIsolatedClient(string $emailHint = 'invoice-detail'): Client
    {
        return Client::create([
            'name' => 'Invoice',
            'first_surname' => 'Detail',
            'second_surname' => null,
            'gmail' => $emailHint.'-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
    }

    private function createIsolatedProduct(string $name = 'Producto Detalle Pedido'): Product
    {
        return Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => $name,
            'description' => 'Producto para probar detalle de pedido',
            'image' => 'default.png',
            'sale_price' => 1500,
            'purchase_price' => 1000,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
    }

    /** @return array{0: Sale, 1: SaleItem} */
    private function createIsolatedSale(Client $client, Product $product, ?string $invoiceNumber = null): array
    {
        $invoice = $invoiceNumber ?? 'CF4-T'.substr(uniqid('', true), -6);

        $sale = Sale::create([
            'invoice_number' => $invoice,
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 1500,
            'iva' => 0,
            'discount' => 0,
            'total' => 1500,
            'notes' => null,
            'order_source' => 'web_cart',
        ]);

        $item = SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 1500,
            'unit_discount' => 0,
            'total' => 1500,
        ]);

        return [$sale, $item];
    }

    private function cleanup(?Sale $sale, ?Product $product, ?Client $client): void
    {
        if ($sale) {
            SaleItem::where('sale_id', $sale->sale_id)->delete();
            $sale->delete();
        }
        if ($product) {
            $product->delete();
        }
        if ($client) {
            $client->delete();
        }
    }

    public function test_authenticated_owner_sees_own_invoice_detail(): void
    {
        $client = $this->createIsolatedClient('cp89-01');
        $product = $this->createIsolatedProduct('Detalle Propio');
        [$sale] = $this->createIsolatedSale($client, $product, 'CF4-T'.substr(uniqid('', true), -5));

        try {
            $this->actingAs($client, 'clients');

            $response = $this->get(route('clients.invoices.show', $sale));
            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Client/Invoices/Show', false)
                ->where('invoiceNumber', $sale->invoice_number)
                ->has('items', 1, fn (Assert $item) => $item
                    ->where('name', 'Detalle Propio')
                    ->etc()
                )
            );
        } finally {
            $this->cleanup($sale, $product, $client);
        }
    }

    public function test_authenticated_client_gets_404_on_foreign_invoice(): void
    {
        $owner = $this->createIsolatedClient('cp89-02-owner');
        $other = $this->createIsolatedClient('cp89-02-other');
        $product = $this->createIsolatedProduct('Detalle Ajeno');
        [$sale] = $this->createIsolatedSale($owner, $product);

        try {
            $this->actingAs($other, 'clients');

            $this->get(route('clients.invoices.show', $sale))->assertNotFound();
        } finally {
            $this->cleanup($sale, $product, $owner);
            $other->delete();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $client = $this->createIsolatedClient('cp89-03');
        $product = $this->createIsolatedProduct('Detalle Guest');
        [$sale] = $this->createIsolatedSale($client, $product);

        try {
            $response = $this->get(route('clients.invoices.show', $sale));
            $this->assertContains($response->status(), [302, 401]);
            if ($response->status() === 302) {
                $location = $response->headers->get('Location') ?? '';
                $this->assertStringContainsString('login', strtolower($location));
            }
        } finally {
            $this->cleanup($sale, $product, $client);
        }
    }

    public function test_cancelled_invoice_detail_shows_cancellation_reason_and_document(): void
    {
        $client = $this->createIsolatedClient('cp89-cancel');
        $product = $this->createIsolatedProduct('Detalle Cancelado');
        [$sale] = $this->createIsolatedSale($client, $product, 'CF4-C'.substr(uniqid('', true), -5));
        $reason = 'Cliente no recogió el pedido a tiempo';

        try {
            $sale->update(['status' => 'cancelled']);

            InventoryMovement::create([
                'product_id' => $product->product_id,
                'user_id' => null,
                'type' => MovementType::CANCELADO,
                'origin' => 'cancellation',
                'quantity' => 1,
                'stock_before' => 10,
                'stock_after' => 11,
                'reference_id' => $sale->sale_id,
                'reason' => $reason,
            ]);

            $this->actingAs($client, 'clients');

            $response = $this->get(route('clients.invoices.show', $sale));
            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Client/Invoices/Show', false)
                ->where('orderMeta.statusLabel', 'Cancelada')
                ->where('orderMeta.cancellationReason', $reason)
            );
        } finally {
            InventoryMovement::where('reference_id', $sale->sale_id)->delete();
            $this->cleanup($sale, $product, $client);
        }
    }

    public function test_authenticated_owner_can_open_invoice_print_view(): void
    {
        $client = $this->createIsolatedClient('cp89-print');
        $product = $this->createIsolatedProduct('Detalle Print');
        [$sale] = $this->createIsolatedSale($client, $product, 'CF4-P'.substr(uniqid('', true), -5));

        try {
            $this->actingAs($client, 'clients');

            $response = $this->get(route('clients.invoices.print', $sale));
            $response->assertOk();
            $response->assertViewIs('client.invoice-print');
            $response->assertDontSee('cliente-header', false);
            $response->assertDontSee('header-menu-toggle', false);
            $response->assertSee($sale->invoice_number, false);
            $response->assertSee('Detalle Print', false);
            $response->assertSee('cf4-auto-print', false);
        } finally {
            $this->cleanup($sale, $product, $client);
        }
    }

    public function test_guest_cannot_open_invoice_print_view(): void
    {
        $client = $this->createIsolatedClient('cp89-print-guest');
        $product = $this->createIsolatedProduct('Print Guest');
        [$sale] = $this->createIsolatedSale($client, $product);

        try {
            $response = $this->get(route('clients.invoices.print', $sale));
            $this->assertContains($response->status(), [302, 401]);
        } finally {
            $this->cleanup($sale, $product, $client);
        }
    }
}
