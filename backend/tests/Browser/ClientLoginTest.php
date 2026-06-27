<?php

namespace Tests\Browser;

use App\Models\Client;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

/**
 * UI test — client login flow (Seguimiento 8 / DevOps).
 */
class ClientLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    #[Group('seguimiento8')]
    #[Group('seguimiento8-arturo')]
    #[Group('seguimiento8-dusk')]
    public function test_client_can_log_in_from_storefront(): void
    {
        Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Dusk',
            'second_surname' => null,
            'gmail' => 'cliente-dusk@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'email_verified' => true,
            'active' => true,
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/login')
                ->waitFor('#public-login-form', 10);
            $this->fillControlledInput($browser, '#login-email', 'cliente-dusk@example.com');
            $this->fillControlledInput($browser, '#login-password', 'password');
            $browser->press('#login-submit-btn')
                ->waitForLocation('/catalog', 15)
                ->assertPathIs('/catalog');
        });
    }
}
