<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin classification-catalog ("Opciones por tipo"): auth, listado de
 * subcategorías, CRUD de dimensiones (atributos) y valores con soft-delete y
 * restauración. Reusa ClassificationCatalogService.
 */
class ClassificationCatalogApiTest extends TestCase
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
            ['gmail' => 'cls-admin@example.com'],
            ['name' => 'Cls', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    /** Subcategoría concreta (con padre), requisito del catálogo. */
    private function subcategory(): Category
    {
        $parent = Category::create(['name' => 'Bicicletas Cls']);

        return Category::create(['name' => 'Montaña Cls', 'parent_category_id' => $parent->category_id]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/classification-catalog')->assertStatus(401);
    }

    public function test_index_lists_subcategories(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->subcategory();

        $this->getJson('/api/v1/admin/classification-catalog')
            ->assertOk()
            ->assertJsonStructure(['data' => ['subcategories', 'pagination']]);
    }

    public function test_dimension_crud_and_values(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $sub = $this->subcategory();

        // Crear atributo (dimensión)
        $this->postJson("/api/v1/admin/classification-catalog/{$sub->category_id}/dimensions", ['label' => 'Color'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.attributes.0.label', 'Color');

        $dimension = ClassificationDimension::where('category_id', $sub->category_id)->firstOrFail();

        // Crear valor
        $this->postJson("/api/v1/admin/classification-catalog/dimensions/{$dimension->id}/values", ['value' => 'Rojo'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.values.0.value', 'Rojo');

        $value = ClassificationValue::where('classification_dimension_id', $dimension->id)->firstOrFail();

        // Renombrar valor
        $this->putJson("/api/v1/admin/classification-catalog/values/{$value->id}", ['value' => 'Rojo Vivo'])
            ->assertOk()
            ->assertJsonPath('data.values.0.value', 'Rojo Vivo');

        // Soft-delete y restore del valor
        $this->deleteJson("/api/v1/admin/classification-catalog/values/{$value->id}")->assertOk();
        $this->assertSoftDeleted('classification_values', ['id' => $value->id]);

        $this->postJson("/api/v1/admin/classification-catalog/values/{$value->id}/restore")
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('classification_values', ['id' => $value->id, 'deleted_at' => null]);
    }

    public function test_dimension_soft_delete_and_restore(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $sub = $this->subcategory();
        $dimension = ClassificationDimension::create([
            'category_id' => $sub->category_id, 'slug' => 'talla', 'label' => 'Talla', 'sort_order' => 0,
        ]);

        $this->deleteJson("/api/v1/admin/classification-catalog/dimensions/{$dimension->id}")->assertOk();
        $this->assertSoftDeleted('classification_dimensions', ['id' => $dimension->id]);

        $this->postJson("/api/v1/admin/classification-catalog/dimensions/{$dimension->id}/restore")
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('classification_dimensions', ['id' => $dimension->id, 'deleted_at' => null]);
    }
}
