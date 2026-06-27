<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CF100AdminCardsToggleTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateAdmin(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Web',
            'second_surname' => null,
            'gmail' => 'cf100-admin-web@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF100',
            'second_surname' => null,
            'gmail' => 'cf100-admin-guard@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($admin);
    }

    public function test_orders_card_toggles_between_pending_filter_and_view_all(): void
    {
        $this->authenticateAdmin();

        Sale::create([
            'invoice_number' => 'INV-CF100-ORD-001',
            'client_id' => null,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 1000,
            'iva' => 0,
            'discount' => 0,
            'total' => 1000,
            'order_source' => 'web_cart',
        ]);

        $baseResponse = $this->get(route('admin.orders.index'));
        $baseResponse->assertStatus(200);
        $baseResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index', false)
            ->where('pendingWebOrdersCount', 1)
            ->where('filters.status', '')
        );

        $filteredResponse = $this->get(route('admin.orders.index', ['status' => 'pending']));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index', false)
            ->where('filters.status', 'pending')
            ->where('pagination.total', 1)
        );
    }

    public function test_supplier_orders_card_toggles_between_open_filter_and_view_all(): void
    {
        $this->authenticateAdmin();

        $supplier = Supplier::create([
            'name' => 'Proveedor CF100',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'proveedor-cf100@example.com',
            'address' => 'Addr',
            'delivery_time' => 3,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-2026-9000',
            'estimated_delivery_date' => now()->addDays(3)->toDateString(),
            'date' => now(),
            'state' => 'confirmed',
            'total' => 1000,
        ]);

        $baseResponse = $this->get(route('admin.supplier-orders.index'));
        $baseResponse->assertStatus(200);
        $baseResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/SupplierOrders/Index', false)
            ->where('openSupplierOrdersCount', 1)
            ->where('filters.state', '')
        );

        $filteredResponse = $this->get(route('admin.supplier-orders.index', ['state' => 'open']));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/SupplierOrders/Index', false)
            ->where('filters.state', 'open')
            ->where('pagination.total', 1)
        );
    }

    public function test_inventory_card_toggles_between_low_stock_filter_and_view_all(): void
    {
        $this->authenticateAdmin();

        Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto Low CF100',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 2000,
            'purchase_price' => 1000,
            'stock_current' => 3,
            'stock_minimum' => 5,
            'status' => 'active',
        ]);

        $baseResponse = $this->get(route('inventory'));
        $baseResponse->assertStatus(200);
        $baseResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Inventory/Index', false)
            ->where('inventorySummary.lowStock', 1)
            ->where('filters.stock_status', '')
        );

        $filteredResponse = $this->get(route('inventory', ['stock_status' => 'low']));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Inventory/Index', false)
            ->where('filters.stock_status', 'low')
            ->where('pagination.total', 1)
        );
    }
}
