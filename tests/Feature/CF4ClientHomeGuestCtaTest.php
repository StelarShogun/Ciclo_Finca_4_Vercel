<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CF4ClientHomeGuestCtaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_create_account_in_final_cta(): void
    {
        $response = $this->get(route('clients.home'));

        $response->assertOk();
        $response->assertSee('Crear cuenta', false);
        $response->assertSee(route('clients.register.form'), false);
    }

    public function test_authenticated_client_does_not_see_create_account_in_final_cta(): void
    {
        Client::create([
            'name' => 'Darwin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'darwin-home-cta@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'email_verified' => true,
        ]);

        $this->post(route('login'), [
            'gmail' => 'darwin-home-cta@example.com',
            'password' => 'password',
        ]);

        $response = $this->get(route('clients.home'));

        $response->assertOk();
        $response->assertDontSee('Crear cuenta', false);
        $response->assertSee('Ir al carrito', false);
    }
}
