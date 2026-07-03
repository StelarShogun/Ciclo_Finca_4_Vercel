<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 home del storefront: estructura (destacados, categorías, hero) y que
 * es público. Reusa StorefrontViewModel::home().
 */
class ClientHomeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    public function test_home_is_public_and_structured(): void
    {
        $parent = Category::create(['name' => 'Bicicletas Home']);
        Supplier::firstOrCreate(['name' => 'Sup Home']);
        Product::factory()->create([
            'category_id' => $parent->category_id,
            'name' => 'Destacada Home',
            'status' => 'active',
            'is_featured' => true,
            'stock_current' => 5,
            'sale_price' => 1000,
            'purchase_price' => 500,
        ]);

        $res = $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonStructure(['data' => ['featuredProducts', 'categories', 'hero' => ['title', 'subtitle'], 'showGuestRegisterCta']]);

        $names = collect($res->json('data.featuredProducts'))->pluck('name');
        $this->assertTrue($names->contains('Destacada Home'));
    }
}
