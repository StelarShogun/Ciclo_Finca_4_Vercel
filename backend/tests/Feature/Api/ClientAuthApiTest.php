<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 auth de cliente: login (cookie de sesión), /me y logout.
 */
class ClientAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        config(['recaptcha.site_key' => null]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function client(): Client
    {
        return Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Activo',
            'second_surname' => null,
            'gmail' => 'cliente-activo@gmail.com',
            'password' => bcrypt('password123'),
            'email_verified' => true,
            'active' => true,
            'provider' => 'local',
        ]);
    }

    public function test_login_establishes_session_and_me_returns_client(): void
    {
        $this->client();

        $this->postJson('/api/v1/auth/login', [
            'gmail' => 'cliente-activo@gmail.com',
            'password' => 'password123',
        ])->assertOk();

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.type', 'client')
            ->assertJsonPath('data.user.gmail', 'cliente-activo@gmail.com');
    }

    public function test_login_rejects_bad_credentials(): void
    {
        $this->client();

        $this->postJson('/api/v1/auth/login', [
            'gmail' => 'cliente-activo@gmail.com',
            'password' => 'incorrecta',
        ])->assertStatus(401);
    }

    public function test_me_requires_session(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }
}
