<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 auth (Sanctum cookie / SPA): login admin, /me y errores.
 * CSRF se omite automáticamente bajo tests; el flujo del XSRF-TOKEN se verifica
 * aparte por curl. Aquí se valida la lógica de credenciales y sesión.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Simula el origen del SPA Next para que Sanctum trate los /api/* como
        // stateful (sesión por cookie). Sin esto no hay session store en tests.
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function admin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'api-admin@example.com'],
            [
                'name' => 'Api',
                'first_surname' => 'Admin',
                'second_surname' => null,
                'password' => bcrypt('password123'),
                'last_access' => now(),
            ]
        );
    }

    public function test_admin_login_succeeds_and_me_returns_admin(): void
    {
        $this->admin();

        $this->postJson('/api/v1/auth/admin/login', [
            'gmail' => 'api-admin@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.type', 'admin')
            ->assertJsonPath('data.user.gmail', 'api-admin@example.com')
            ->assertJsonMissingPath('data.user.password');

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.type', 'admin')
            ->assertJsonPath('data.user.gmail', 'api-admin@example.com');
    }

    public function test_admin_login_with_bad_credentials_fails(): void
    {
        $this->admin();

        $this->postJson('/api/v1/auth/admin/login', [
            'gmail' => 'api-admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors('gmail');

        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_me_without_session_is_unauthenticated(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_admin_logout_clears_session(): void
    {
        $this->admin();

        $this->postJson('/api/v1/auth/admin/login', [
            'gmail' => 'api-admin@example.com',
            'password' => 'password123',
        ])->assertOk();

        $this->postJson('/api/v1/auth/admin/logout')->assertOk();

        $this->getJson('/api/v1/me')->assertStatus(401);
    }
}
