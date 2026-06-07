<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientRegisterBrowserTest extends DuskTestCase
{
    use RefreshDatabase;

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
