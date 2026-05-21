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

    public function test_admin_clients_index_filters_by_created_date(): void
    {
        $admin = $this->makeAdmin();
        Auth::guard('admin')->login($admin);

        $match = Client::create([
            'name' => 'CreatedMatch',
            'first_surname' => 'Client',
            'second_surname' => null,
            'gmail' => 'admin-clients-created-match-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'active' => true,
        ]);
        Client::query()->whereKey($match->user_id)->update([
            'created_at' => '2026-03-15 10:00:00',
            'updated_at' => '2026-03-15 10:00:00',
        ]);

        $other = Client::create([
            'name' => 'CreatedOther',
            'first_surname' => 'Client',
            'second_surname' => null,
            'gmail' => 'admin-clients-created-other-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'active' => true,
        ]);
        Client::query()->whereKey($other->user_id)->update([
            'created_at' => '2026-01-10 10:00:00',
            'updated_at' => '2026-01-10 10:00:00',
        ]);

        try {
            $response = $this->get(route('admin.clients.index', [
                'created_date' => '2026-03-15',
            ]));

            $response->assertOk();
            $response->assertSee('CreatedMatch', false);
            $response->assertDontSee('CreatedOther', false);
        } finally {
            Client::query()->whereIn('user_id', [(int) $match->user_id, (int) $other->user_id])->delete();
            $admin->delete();
        }
    }

    public function test_admin_clients_index_filters_by_updated_date(): void
    {
        $admin = $this->makeAdmin();
        Auth::guard('admin')->login($admin);

        $match = Client::create([
            'name' => 'UpdatedMatch',
            'first_surname' => 'Client',
            'second_surname' => null,
            'gmail' => 'admin-clients-updated-match-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'active' => true,
        ]);
        Client::query()->whereKey($match->user_id)->update([
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-05-20 14:00:00',
        ]);

        $other = Client::create([
            'name' => 'UpdatedOther',
            'first_surname' => 'Client',
            'second_surname' => null,
            'gmail' => 'admin-clients-updated-other-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'active' => true,
        ]);
        Client::query()->whereKey($other->user_id)->update([
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-04-01 14:00:00',
        ]);

        try {
            $response = $this->get(route('admin.clients.index', [
                'updated_date' => '2026-05-20',
            ]));

            $response->assertOk();
            $response->assertSee('UpdatedMatch', false);
            $response->assertDontSee('UpdatedOther', false);
        } finally {
            Client::query()->whereIn('user_id', [(int) $match->user_id, (int) $other->user_id])->delete();
            $admin->delete();
        }
    }

    public function test_admin_clients_index_sorts_by_column_and_direction(): void
    {
        $admin = $this->makeAdmin();
        Auth::guard('admin')->login($admin);

        $zebra = Client::create([
            'name' => 'Zebra',
            'first_surname' => 'Sort',
            'second_surname' => null,
            'gmail' => 'admin-clients-sort-z-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'active' => true,
        ]);
        $alpha = Client::create([
            'name' => 'Alpha',
            'first_surname' => 'Sort',
            'second_surname' => null,
            'gmail' => 'admin-clients-sort-a-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'active' => true,
        ]);

        try {
            $asc = $this->get(route('admin.clients.index', [
                'search' => 'Sort',
                'sort' => 'name',
                'dir' => 'asc',
            ]));
            $asc->assertOk();
            $asc->assertSeeInOrder(['Alpha', 'Zebra'], false);

            $desc = $this->get(route('admin.clients.index', [
                'search' => 'Sort',
                'sort' => 'name',
                'dir' => 'desc',
            ]));
            $desc->assertOk();
            $desc->assertSeeInOrder(['Zebra', 'Alpha'], false);
            $desc->assertSee('th-sort is-active', false);
            $desc->assertSee('fa-sort-down', false);
        } finally {
            Client::query()->whereIn('user_id', [(int) $zebra->user_id, (int) $alpha->user_id])->delete();
            $admin->delete();
        }
    }
}
