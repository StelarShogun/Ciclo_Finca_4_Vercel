<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * UI test — public catalog page (Seguimiento 8 / DevOps).
 */
class ClientCatalogTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_guest_can_open_catalog_and_see_hero(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/catalog')
                ->waitFor('.catalog-shell', 15)
                ->assertSee('Catálogo de Productos')
                ->assertPresent('.catalog-hero');
        });
    }
}
