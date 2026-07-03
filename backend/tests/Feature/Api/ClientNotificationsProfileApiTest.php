<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 notificaciones (lista + marcar todas leídas) y perfil (ver + editar).
 */
class ClientNotificationsProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function client(): Client
    {
        return Client::create([
            'name' => 'Perfila', 'first_surname' => 'Test', 'second_surname' => null,
            'gmail' => 'perfila@gmail.com', 'password' => bcrypt('password123'),
            'email_verified' => true, 'active' => true, 'provider' => 'local',
        ]);
    }

    public function test_notifications_require_auth(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
        $this->getJson('/api/v1/profile')->assertStatus(401);
    }

    public function test_notifications_index_and_mark_all(): void
    {
        $this->actingAs($this->client(), 'clients');

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonStructure(['data' => ['notifications', 'pagination']]);

        $this->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_profile_show_and_update(): void
    {
        $this->actingAs($this->client(), 'clients');

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.gmail', 'perfila@gmail.com');

        $this->putJson('/api/v1/profile', [
            'name' => 'Perfila Editada',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'perfila@gmail.com',
        ])->assertOk();

        $this->assertDatabaseHas('client_table', ['gmail' => 'perfila@gmail.com', 'name' => 'Perfila Editada']);
    }

    public function test_profile_update_validates(): void
    {
        $this->actingAs($this->client(), 'clients');

        $this->putJson('/api/v1/profile', ['name' => '', 'first_surname' => '', 'gmail' => 'no-email'])
            ->assertStatus(422);
    }
}
