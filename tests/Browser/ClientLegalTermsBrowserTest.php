<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientLegalTermsBrowserTest extends DuskTestCase
{
    use RefreshDatabase;

    public function test_legal_terms_page_is_reachable_from_storefront(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/legal/terminos')
                ->waitForText('Términos y condiciones')
                ->assertSee('Catálogo, precios y disponibilidad');
        });
    }
}
