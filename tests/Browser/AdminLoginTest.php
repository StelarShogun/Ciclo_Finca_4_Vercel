<?php

namespace Tests\Browser;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * UI test — admin login flow (Seguimiento 8 / DevOps).
 */
class AdminLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_log_in_and_reach_dashboard(): void
    {
        AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Dusk',
            'second_surname' => null,
            'gmail' => 'admin-dusk@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/admin/login')
                ->waitFor('#formLogin', 10)
                ->type('#loginEmail', 'admin-dusk@example.com')
                ->type('#loginPassword', 'password')
                ->press('#btnLoginSubmit')
                ->waitForLocation('/dashboard', 15)
                ->assertPathIs('/dashboard');
        });
    }
}
