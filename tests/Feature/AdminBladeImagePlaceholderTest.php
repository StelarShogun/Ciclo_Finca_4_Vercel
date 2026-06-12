<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminBladeImagePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: int, 3: int}>
     */
    public static function adminBladePlaceholderSurfacesProvider(): array
    {
        return [
            'dashboard low stock table' => ['dashboard', 'Grupo Bajo Stock Sin Foto', 2, 5],
            'inventory list' => ['inventory', 'Grupo Sin Foto Admin', 5, 1],
        ];
    }

    #[DataProvider('adminBladePlaceholderSurfacesProvider')]
    public function test_admin_blade_surface_shows_category_icon_placeholder(
        string $routeName,
        string $productName,
        int $stockCurrent,
        int $stockMinimum,
    ): void {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Placeholder',
            'second_surname' => null,
            'gmail' => 'admin-blade-placeholder-'.$routeName.'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
        $root = Category::create([
            'name' => 'Componentes',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'Transmisión',
            'parent_category_id' => $root->category_id,
        ]);
        Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => $productName,
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100000,
            'purchase_price' => 50000,
            'stock_current' => $stockCurrent,
            'stock_minimum' => $stockMinimum,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route($routeName));

        $response->assertOk();
        $response->assertSee('product-media-placeholder--thumb-table', false);
        $response->assertSee('fa-cogs', false);
        $response->assertSee($productName, false);
    }
}
