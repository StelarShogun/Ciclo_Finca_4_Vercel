<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Smoke tests for admin modal markup across key modules.
 */
class CF4AdminModalsSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available: '.$e->getMessage());
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('CF4AdminModalsSmokeTest requires MySQL.');
        }
    }

    private function authenticateAdmin(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Modal',
            'second_surname' => null,
            'gmail' => 'cf4-modals-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $admin = AdminUser::create([
            'name' => 'Modal',
            'first_surname' => 'Smoke',
            'second_surname' => null,
            'gmail' => 'cf4-modals-admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($admin);
    }

    public function test_inventory_modals_include_dialog_semantics(): void
    {
        $this->authenticateAdmin();

        $response = $this->get(route('inventory'));

        $response->assertOk();
        $response->assertSee('id="edit-modal"', false);
        $response->assertSee('id="view-product-modal"', false);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
    }

    public function test_sales_page_includes_view_and_new_sale_modals(): void
    {
        $this->authenticateAdmin();

        $response = $this->get(route('sales.index'));

        $response->assertOk();
        $response->assertSee('id="view-sale-modal"', false);
        $response->assertSee('id="new-sale-modal"', false);
        $response->assertSee('aria-labelledby="view-sale-modal-title"', false);
    }

    public function test_brands_page_modal_uses_active_overlay_pattern(): void
    {
        $this->authenticateAdmin();

        $response = $this->get(route('brands.index'));

        $response->assertOk();
        $response->assertSee('id="modal-marca"', false);
        $response->assertSee('class="modal-overlay"', false);
        $response->assertSee('aria-label="Cerrar"', false);
        $response->assertDontSee('style="display:none;"', false);
    }

    public function test_suppliers_page_includes_detail_and_form_modals(): void
    {
        $this->authenticateAdmin();

        $response = $this->get(route('suppliers.index'));

        $response->assertOk();
        $response->assertSee('id="modalDetalleProveedor"', false);
        $response->assertSee('id="new-supplier-modal"', false);
        $response->assertSee('id="edit-supplier-modal"', false);
    }
}
