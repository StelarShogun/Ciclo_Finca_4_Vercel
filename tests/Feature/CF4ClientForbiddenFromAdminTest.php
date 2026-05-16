<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CF4ClientForbiddenFromAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_session_cannot_open_admin_dashboard_html(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Tienda',
            'second_surname' => null,
            'gmail' => 'cliente-no-admin@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $this->actingAs($client, 'clients')
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_client_session_cannot_open_admin_dashboard_json(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Api',
            'second_surname' => null,
            'gmail' => 'cliente-api@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $res = $this->actingAs($client, 'clients')
            ->getJson(route('dashboard.data'));

        $res->assertStatus(403);
        $this->assertSame('Forbidden', $res->json('error'));
    }

    public function test_guest_still_redirected_to_admin_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-dash@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('dashboard'))
            ->assertOk();
    }
}
