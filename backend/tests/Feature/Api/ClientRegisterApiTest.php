<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * API v1 registro + verificación de cliente. El registro crea el cliente sin
 * verificar y envía un código; verify lo valida y establece la sesión.
 */
class ClientRegisterApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
        Mail::fake();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nueva',
            'first_surname' => 'Clienta',
            'second_surname' => null,
            'gmail' => 'nueva.clienta@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'accept_terms' => true,
        ], $overrides);
    }

    public function test_register_creates_unverified_client(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pending_gmail', 'nueva.clienta@gmail.com');

        $this->assertDatabaseHas('client_table', [
            'gmail' => 'nueva.clienta@gmail.com',
            'email_verified' => false,
        ]);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        Client::create([
            'name' => 'Existe', 'first_surname' => 'Ya', 'second_surname' => null,
            'gmail' => 'nueva.clienta@gmail.com', 'password' => bcrypt('x'),
            'email_verified' => true, 'active' => true, 'provider' => 'local',
        ]);

        $this->postJson('/api/v1/auth/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('gmail');
    }

    public function test_register_then_verify_establishes_session(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload())->assertCreated();

        $code = Client::where('gmail', 'nueva.clienta@gmail.com')->value('verification_code');
        $this->assertNotNull($code);

        $this->postJson('/api/v1/auth/verify', ['verification_code' => $code])->assertOk();

        $this->assertDatabaseHas('client_table', [
            'gmail' => 'nueva.clienta@gmail.com',
            'email_verified' => true,
        ]);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.type', 'client');
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload())->assertCreated();

        $this->postJson('/api/v1/auth/verify', ['verification_code' => '000000'])
            ->assertStatus(422);
    }
}
