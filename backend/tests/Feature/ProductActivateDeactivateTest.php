<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Models\AdminUser;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductActivateDeactivateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(LogSensitiveAdminModuleAccess::class);
    }

    private function createAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Toggle',
            'second_surname' => null,
            'gmail' => 'admin-product-toggle-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    private function createProduct(string $status = 'active'): Product
    {
        return Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto Toggle QA',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 500,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => $status,
        ]);
    }

    public function test_deactivate_sets_status_inactive(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct('active');

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('products.destroy', $product->product_id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'inactive');

        $this->assertSame('inactive', $product->fresh()->status);
    }

    public function test_deactivate_is_idempotent_when_already_inactive(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct('inactive');

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('products.destroy', $product->product_id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_inactive', true);

        $this->assertSame('inactive', $product->fresh()->status);
    }

    public function test_activate_sets_status_active(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct('inactive');

        $this->actingAs($admin, 'admin')
            ->patchJson(route('products.activate', $product->product_id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'active');

        $this->assertSame('active', $product->fresh()->status);
    }

    public function test_activate_is_idempotent_when_already_active(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct('active');

        $this->actingAs($admin, 'admin')
            ->patchJson(route('products.activate', $product->product_id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_active', true);

        $this->assertSame('active', $product->fresh()->status);
    }
}
