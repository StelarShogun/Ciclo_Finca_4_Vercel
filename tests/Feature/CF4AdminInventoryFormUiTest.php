<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4 — Admin inventory product modals use shared file-upload UI.
 */
class CF4AdminInventoryFormUiTest extends TestCase
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
            $this->markTestSkipped('CF4AdminInventoryFormUiTest requires MySQL.');
        }

        foreach (['admins', 'client_table', 'products', 'categories'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Missing table: '.$table);
            }
        }
    }

    private function authenticateAdmin(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Web',
            'second_surname' => null,
            'gmail' => 'cf4-inventory-ui-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $admin = AdminUser::create([
            'name' => 'Inventory',
            'first_surname' => 'UI',
            'second_surname' => null,
            'gmail' => 'cf4-inventory-ui-admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($admin);
    }

    public function test_inventory_page_includes_styled_file_upload_zones(): void
    {
        $this->authenticateAdmin();

        $response = $this->get(route('inventory'));

        $response->assertOk();
        $response->assertSee('cf-file-upload', false);
        $response->assertSee('id="new-image"', false);
        $response->assertSee('id="new-subcategory-search"', false);
        $response->assertSee('form-section', false);
        $response->assertSee('data-action="deactivate"', false);
        $response->assertSee('fa-ban', false);
    }
}
