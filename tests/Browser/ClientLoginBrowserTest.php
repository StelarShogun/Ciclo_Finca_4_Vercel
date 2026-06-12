<?php

namespace Tests\Browser;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientLoginBrowserTest extends DuskTestCase
{
    use RefreshDatabase;

    public function test_client_login_redirects_to_catalog(): void
    {
        Client::create([
            'name' => 'Ana',
            'first_surname' => 'Dusk',
            'second_surname' => null,
            'gmail' => 'dusk-login@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'email_verified' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Bienvenido de nuevo')
                ->type('gmail', 'dusk-login@example.com')
                ->type('password', 'password')
                ->press('Iniciar Sesión')
                ->waitForLocation('/catalog')
                ->assertPathIs('/catalog')
                ->assertSee('Catálogo de Productos');
        });
    }
}
