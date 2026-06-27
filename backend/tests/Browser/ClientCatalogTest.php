<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

/**
 * UI test — public catalog page (Seguimiento 8 / DevOps).
 */
class ClientCatalogTest extends DuskTestCase
{
    use DatabaseMigrations;

    #[Group('seguimiento8')]
    #[Group('seguimiento8-darwin')]
    #[Group('seguimiento8-dusk')]
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
