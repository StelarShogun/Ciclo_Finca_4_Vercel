<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin brands: auth, listado paginado/filtrado, alta con detección de
 * duplicados (exacto e insensible a mayúsculas), edición y borrado bloqueado
 * cuando hay productos asociados.
 */
class BrandsApiTest extends TestCase
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
            ['gmail' => 'brand-admin@example.com'],
            ['name' => 'Brand', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/brands')->assertStatus(401);
    }

    public function test_lists_and_filters_brands(): void
    {
        $this->actingAs($this->admin(), 'admin');
        Brand::create(['name' => 'Trek']);
        Brand::create(['name' => 'Giant']);

        $this->getJson('/api/v1/admin/brands')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total'])
            ->assertJsonPath('total', 2);

        $this->getJson('/api/v1/admin/brands?name=Tre')
            ->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Trek');
    }

    public function test_creates_brand(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->postJson('/api/v1/admin/brands', ['name' => 'Specialized'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('brand.name', 'Specialized');

        $this->assertDatabaseHas('brands', ['name' => 'Specialized']);
    }

    public function test_rejects_case_insensitive_duplicate(): void
    {
        $this->actingAs($this->admin(), 'admin');
        Brand::create(['name' => 'Cannondale']);

        $this->postJson('/api/v1/admin/brands', ['name' => 'cannondale'])
            ->assertStatus(422)
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('exact', false);
    }

    public function test_updates_brand(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $brand = Brand::create(['name' => 'Old Name']);

        $this->putJson("/api/v1/admin/brands/{$brand->id}", ['name' => 'New Name'])
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'New Name']);
    }

    public function test_deletes_unused_brand(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $brand = Brand::create(['name' => 'Disposable']);

        $this->deleteJson("/api/v1/admin/brands/{$brand->id}")
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }
}
