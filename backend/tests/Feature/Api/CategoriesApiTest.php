<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin categories: auth, jerarquía (padres + subcategorías), alta de
 * categoría padre y de subcategoría con unicidad por padre.
 */
class CategoriesApiTest extends TestCase
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
            ['gmail' => 'cat-admin@example.com'],
            ['name' => 'Cat', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/categories')->assertStatus(401);
    }

    public function test_index_returns_parents_and_hierarchy(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $parent = Category::create(['name' => 'Bicicletas']);
        Category::create(['name' => 'Montaña', 'parent_category_id' => $parent->category_id]);

        $res = $this->getJson('/api/v1/admin/categories')->assertOk()
            ->assertJsonStructure(['data' => ['parents', 'hierarchy']]);

        $res->assertJsonPath('data.parents.0.name', 'Bicicletas');
        // Jerarquía: padre primero, sub después.
        $res->assertJsonPath('data.hierarchy.0.is_parent', true);
        $this->assertSame('Montaña', $res->json('data.hierarchy.1.name'));
        $this->assertSame('Bicicletas', $res->json('data.hierarchy.1.parent_name'));
    }

    public function test_creates_parent_category(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->postJson('/api/v1/admin/categories/parent', ['name' => 'Accesorios'])
            ->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('categories', ['name' => 'Accesorios', 'parent_category_id' => null]);
    }

    public function test_creates_subcategory(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $parent = Category::create(['name' => 'Cascos']);

        $this->postJson('/api/v1/admin/categories/subcategory', [
            'name' => 'Integral',
            'parent_category_id' => $parent->category_id,
        ])->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('categories', ['name' => 'Integral', 'parent_category_id' => $parent->category_id]);
    }

    public function test_subcategory_requires_existing_parent(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->postJson('/api/v1/admin/categories/subcategory', ['name' => 'Huérfana', 'parent_category_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_category_id');
    }

    public function test_duplicate_parent_name_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin');
        Category::create(['name' => 'Repetida']);

        $this->postJson('/api/v1/admin/categories/parent', ['name' => 'Repetida'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }
}
