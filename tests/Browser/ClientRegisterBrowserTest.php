<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

class ClientRegisterBrowserTest extends DuskTestCase
{
    use RefreshDatabase;

    #[Group('seguimiento8')]
    #[Group('seguimiento8-darwin')]
    #[Group('seguimiento8-dusk')]
    public function test_register_page_shows_signup_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->waitForText('Crear Cuenta')
                ->assertSee('Nombre')
                ->assertSee('Correo Electrónico')
                ->assertPresent('#formRegistroCliente')
                ->clickLink('Iniciar sesión')
                ->waitForLocation('/login')
                ->assertSee('Bienvenido de nuevo');
        });
    }
}
