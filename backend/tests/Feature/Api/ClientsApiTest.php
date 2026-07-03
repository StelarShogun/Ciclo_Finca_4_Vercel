<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin clients: auth, listado filtrable, historial de compras y
 * bloqueo/desbloqueo (con auditoría).
 */
class ClientsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function admin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'cli-admin@example.com'],
            ['name' => 'Cli', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    private function client(array $overrides = []): Client
    {
        static $n = 0;
        $n++;

        return Client::create(array_merge([
            'name' => 'Cliente',
            'first_surname' => 'Prueba',
            'second_surname' => null,
            'gmail' => "cliente{$n}@example.com",
            'password' => bcrypt('password'),
            'provider' => 'local',
            'active' => true,
        ], $overrides));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/clients')->assertStatus(401);
    }

    public function test_index_lists_and_filters(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->client(['name' => 'Ana', 'gmail' => 'ana@example.com']);
        $this->client(['name' => 'Beto', 'gmail' => 'beto@example.com', 'active' => false]);

        $this->getJson('/api/v1/admin/clients')
            ->assertOk()
            ->assertJsonStructure(['data' => ['clients', 'pagination', 'filters', 'sort', 'dir']]);

        $this->getJson('/api/v1/admin/clients?search=Ana')
            ->assertOk()->assertJsonPath('data.pagination.total', 1);

        $this->getJson('/api/v1/admin/clients?status=banned')
            ->assertOk()->assertJsonPath('data.clients.0.name', 'Beto');
    }

    public function test_show_returns_purchase_history(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $client = $this->client();

        $this->getJson("/api/v1/admin/clients/{$client->user_id}")
            ->assertOk()
            ->assertJsonPath('data.clientId', (int) $client->user_id)
            ->assertJsonStructure(['data' => ['displayName', 'gmail', 'orders']]);
    }

    public function test_ban_and_unban(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $client = $this->client(['active' => true]);

        $this->postJson("/api/v1/admin/clients/{$client->user_id}/ban")
            ->assertOk()->assertJsonPath('success', true);
        $this->assertFalse((bool) $client->fresh()->active);

        $this->postJson("/api/v1/admin/clients/{$client->user_id}/unban")
            ->assertOk()->assertJsonPath('success', true);
        $this->assertTrue((bool) $client->fresh()->active);
    }
}
