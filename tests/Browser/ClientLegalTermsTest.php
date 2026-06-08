<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

/**
 * UI test — legal terms page (Seguimiento 8 / DevOps).
 */
class ClientLegalTermsTest extends DuskTestCase
{
    use DatabaseMigrations;

    #[Group('seguimiento8')]
    #[Group('seguimiento8-dilan')]
    #[Group('seguimiento8-dusk')]
    public function test_guest_can_read_terms_and_conditions_page(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/legal/terminos')
                ->waitFor('#legal-page-title', 15)
                ->assertSee('Términos y condiciones')
                ->assertSee('Uso del sitio');
        });
    }
}
