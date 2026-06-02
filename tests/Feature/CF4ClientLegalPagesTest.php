<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CF4ClientLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_accessible(): void
    {
        $this->get(route('clients.legal.terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Legal/Terms', false)
                ->where('legalTitle', 'Términos y condiciones')
            );

        $this->get(route('clients.legal.privacy'))->assertOk()->assertSee('Política de privacidad', false);
        $this->get(route('clients.legal.returns'))->assertOk()->assertSee('Cambios, devoluciones', false);
        $this->get(route('clients.contact'))->assertOk()->assertSee('Contacto', false);
    }

    public function test_home_is_accessible_after_inertia_migration(): void
    {
        $this->get(route('clients.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Home/Index', false)
            );
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
