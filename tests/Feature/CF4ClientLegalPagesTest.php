<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CF4ClientLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_accessible(): void
    {
        $this->get(route('clients.legal.terms'))->assertOk()->assertSee('Términos y condiciones', false);
        $this->get(route('clients.legal.privacy'))->assertOk()->assertSee('Política de privacidad', false);
        $this->get(route('clients.legal.returns'))->assertOk()->assertSee('Cambios, devoluciones', false);
        $this->get(route('clients.contact'))->assertOk()->assertSee('Contacto', false);
    }

    public function test_home_footer_shows_legal_links(): void
    {
        $this->get(route('clients.home'))
            ->assertOk()
            ->assertSee(route('clients.legal.terms'), false)
            ->assertSee(route('clients.legal.privacy'), false)
            ->assertSee(route('clients.legal.returns'), false)
            ->assertSee(route('clients.contact'), false);
    }

    public function test_register_requires_terms_acceptance(): void
    {
        $response = $this->post(route('clients.register'), [
            'name' => 'Juan',
            'first_surname' => 'Pérez',
            'second_surname' => null,
            'gmail' => 'juan.legal@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('accept_terms');
    }

}
