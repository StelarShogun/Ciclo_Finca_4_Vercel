<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class CF4ClientLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Group('seguimiento8')]
    #[Group('seguimiento8-dilan')]
    public function test_legal_pages_are_accessible(): void
    {
        $this->get(route('clients.legal.terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Legal/Terms', false)
                ->where('legalTitle', 'Términos y condiciones')
            );

        $this->get(route('clients.legal.privacy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Legal/Privacy', false)
                ->where('legalTitle', 'Política de privacidad')
            );

        $this->get(route('clients.legal.returns'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Legal/Returns', false)
                ->where('legalTitle', 'Cambios, devoluciones y cancelaciones')
            );

        $this->get(route('clients.contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Legal/Contact', false)
                ->where('legalTitle', 'Contacto')
            );
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
