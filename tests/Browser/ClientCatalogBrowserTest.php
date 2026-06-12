<?php

namespace Tests\Browser;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientCatalogBrowserTest extends DuskTestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_active_products(): void
    {
        Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Bici Dusk CF4',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 150000,
            'purchase_price' => 50000,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/catalog')
                ->waitForText('Catálogo de Productos')
                ->assertSee('Bici Dusk CF4');
        });
    }
}
