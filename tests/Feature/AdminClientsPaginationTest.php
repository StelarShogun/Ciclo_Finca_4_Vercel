<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminClientsPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            foreach (['client_table', 'admins'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Missing table: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available: '.$e->getMessage());
        }
    }

    private function makeAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Test',
            'first_surname' => 'Admin',
            'second_surname' => null,
            'gmail' => 'admin-clients-pager-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    /**
     * @return list<int>
     */
    private function seedClients(int $count): array
    {
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $client = Client::create([
                'name' => 'Pager Client '.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'first_surname' => 'Test',
                'second_surname' => null,
                'gmail' => 'admin-clients-pager-'.uniqid('', true).'@example.com',
                'password' => bcrypt('password'),
                'provider' => 'local',
                'active' => true,
            ]);
            $ids[] = (int) $client->user_id;
        }

        return $ids;
    }

    public function test_admin_clients_index_paginates_with_shared_component(): void
    {
        $admin = $this->makeAdmin();
        Auth::guard('admin')->login($admin);

        $ids = $this->seedClients(12);

        try {
            $page1 = $this->get(route('admin.clients.index', ['per_page' => 10]));

            $page1->assertOk();
            $page1->assertSee('cf4-pagination-toolbar', false);
            $page1->assertSee('Mostrando 1–10 de', false);
            $page1->assertSee('data-page="2"', false);
            $page1->assertSee('data-cf4-ajax-pagination', false);

            $page2 = $this->get(route('admin.clients.index', [
                'per_page' => 10,
                'page' => 2,
            ]));

            $page2->assertOk();
            $page2->assertSee('Mostrando 11–12 de', false);
        } finally {
            Client::query()->whereIn('user_id', $ids)->delete();
            $admin->delete();
        }
    }
}
