<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CF4ClientHomeGuestCtaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_create_account_in_final_cta(): void
    {
        $response = $this->get(route('clients.home'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Home/Index', false)
                ->where('showGuestRegisterCta', true)
                ->where('auth.client', null)
            );
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

        $client = Client::query()->where('gmail', 'darwin-home-cta@example.com')->firstOrFail();
        $response = $this->actingAs($client, 'clients')->get(route('clients.home'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Home/Index', false)
                ->where('showGuestRegisterCta', false)
                ->where('auth.client.gmail', 'darwin-home-cta@example.com')
            );
    }
}
