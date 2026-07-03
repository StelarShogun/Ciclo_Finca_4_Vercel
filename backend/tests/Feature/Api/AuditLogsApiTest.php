<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin audit-logs: auth, listado filtrable (solo lectura).
 */
class AuditLogsApiTest extends TestCase
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
            ['gmail' => 'audit-admin@example.com'],
            ['name' => 'Audit', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/audit-logs')->assertStatus(401);
    }

    public function test_index_returns_logs(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        AuditLog::create([
            'admin_user_id' => $admin->user_id,
            'admin_email_snapshot' => $admin->gmail,
            'action_type' => 'product_create',
            'module' => 'products',
            'description' => 'Producto creado.',
            'meta' => ['x' => 1],
        ]);

        $this->getJson('/api/v1/admin/audit-logs')
            ->assertOk()
            ->assertJsonStructure(['data' => ['logs', 'pagination', 'actionTypeOptions', 'moduleOptions', 'filters']])
            ->assertJsonPath('data.logs.0.action_type', 'product_create');
    }

    public function test_filters_by_module(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        AuditLog::create([
            'admin_user_id' => $admin->user_id,
            'admin_email_snapshot' => $admin->gmail,
            'action_type' => 'client_ban',
            'module' => 'clients',
            'description' => 'Cliente bloqueado.',
            'meta' => [],
        ]);

        $this->getJson('/api/v1/admin/audit-logs?module=clients')
            ->assertOk()
            ->assertJsonPath('data.logs.0.module', 'clients');

        $this->getJson('/api/v1/admin/audit-logs?module=products')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0);
    }
}
