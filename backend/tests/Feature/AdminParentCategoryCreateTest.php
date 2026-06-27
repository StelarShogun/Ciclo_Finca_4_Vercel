<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Models\AdminUser;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * CF4-66 — Crear categorías padre desde el panel administrativo.
 *
 * Cubre los casos de prueba CP-01..CP-06 definidos en la HU CF4-66:
 *   CP-01: Intentar crear categoría padre sin nombre.
 *   CP-02: Crear categoría padre con nombre y sin descripción.
 *   CP-03: Crear categoría padre con nombre y descripción.
 *   CP-04: Intentar crear rubro con nombre ya existente.
 *   CP-05: Crear rubro válido nuevo.
 *   CP-06: Ir a pantalla donde se selecciona categoría padre y verificar disponibilidad.
 */
class AdminParentCategoryCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Skip the sensitive-access audit middleware; other admin tests do the same.
        $this->withoutMiddleware(LogSensitiveAdminModuleAccess::class);
    }

    private function loginAdmin(): AdminUser
    {
        $admin = AdminUser::create([
            'name' => 'Parent',
            'first_surname' => 'Admin',
            'second_surname' => null,
            'gmail' => 'cf4-66-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
        Auth::guard('admin')->login($admin);

        return $admin;
    }

    /** CP-01: intentar crear categoría padre sin nombre — el sistema bloquea y muestra validación (CA1). */
    public function test_cp01_rejects_create_without_name(): void
    {
        $this->loginAdmin();

        $response = $this->from(route('categories.parents.create'))
            ->post(route('categories.parents.store'), [
                'name' => '',
                'description' => 'Sin nombre',
            ]);

        $response->assertRedirect(route('categories.parents.create'));
        $response->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('categories', [
            'description' => 'Sin nombre',
            'parent_category_id' => null,
        ]);
    }

    /** CP-02: crear categoría padre con nombre y sin descripción — se guarda correctamente (CA2). */
    public function test_cp02_creates_with_name_and_without_description(): void
    {
        $this->loginAdmin();

        $name = 'Categoría CP02 '.uniqid();

        $response = $this->post(route('categories.parents.store'), [
            'name' => $name,
            'description' => '',
        ]);

        $response->assertRedirect(route('categories.parents.create'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('categories', [
            'name' => $name,
            'parent_category_id' => null,
        ]);
    }

    /** CP-03: crear categoría padre con nombre y descripción — se guarda y se conserva la descripción (CA2 + CA4). */
    public function test_cp03_creates_with_name_and_description(): void
    {
        $this->loginAdmin();

        $name = 'Categoría CP03 '.uniqid();
        $description = 'Descripción persistida en CP-03';

        $response = $this->post(route('categories.parents.store'), [
            'name' => $name,
            'description' => $description,
        ]);

        $response->assertRedirect(route('categories.parents.create'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('categories', [
            'name' => $name,
            'description' => $description,
            'parent_category_id' => null,
        ]);
    }

    /** CP-04: intentar crear rubro con nombre ya existente — el sistema rechaza el duplicado (CA3). */
    public function test_cp04_rejects_duplicate_name(): void
    {
        $this->loginAdmin();

        $name = 'Categoría CP04 '.uniqid();

        Category::create([
            'name' => $name,
            'description' => null,
            'parent_category_id' => null,
        ]);

        $response = $this->from(route('categories.parents.create'))
            ->post(route('categories.parents.store'), [
                'name' => $name,
                'description' => 'Intento duplicado',
            ]);

        $response->assertRedirect(route('categories.parents.create'));
        $response->assertSessionHasErrors('name');

        // No se inserta una segunda categoría padre con el mismo nombre.
        $this->assertSame(
            1,
            Category::whereNull('parent_category_id')->where('name', $name)->count()
        );
    }

    /** CP-05: crear rubro válido nuevo — el rubro se persiste y muestra confirmación al usuario (CA4). */
    public function test_cp05_creates_valid_new_rubro_and_confirms(): void
    {
        $this->loginAdmin();

        $name = 'Categoría CP05 '.uniqid();

        $response = $this->post(route('categories.parents.store'), [
            'name' => $name,
            'description' => 'Descripción opcional',
        ]);

        $response->assertRedirect(route('categories.parents.create'));
        $response->assertSessionHas('status', 'Categoría creada correctamente.');

        $this->assertDatabaseHas('categories', [
            'name' => $name,
            'description' => 'Descripción opcional',
            'parent_category_id' => null,
        ]);
    }

    /** CP-06: en la pantalla de selección de categoría padre el rubro nuevo está disponible (CA5). */
    public function test_cp06_new_rubro_appears_in_parent_selector(): void
    {
        $this->loginAdmin();

        $name = 'Categoría CP06 '.uniqid();

        $this->post(route('categories.parents.store'), [
            'name' => $name,
            'description' => null,
        ])->assertRedirect(route('categories.parents.create'));

        $response = $this->get(route('categories.subcategories.create'));
        $response->assertOk();

        $created = Category::whereNull('parent_category_id')->where('name', $name)->firstOrFail();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Categories/CreateSubcategory', false)
            ->where('categories.0.category_id', (int) $created->category_id)
            ->where('categories.0.name', $name)
        );
    }

    public function test_subcategory_create_hierarchy_table_is_paginated(): void
    {
        $this->loginAdmin();

        $parent = Category::create([
            'name' => 'Padre paginación '.uniqid(),
            'parent_category_id' => null,
        ]);

        for ($i = 0; $i < 18; $i++) {
            Category::create([
                'name' => 'Sub paginación '.$i.' '.uniqid(),
                'parent_category_id' => $parent->category_id,
            ]);
        }

        $pageOne = $this->get(route('categories.subcategories.create', ['hierarchy_page' => 1, 'per_page' => 10]));
        $pageOne->assertOk();
        $pageOne->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Categories/CreateSubcategory', false)
            ->where('pagination.currentPage', 1)
            ->where('pagination.perPage', 10)
            ->where('pagination.total', 19)
        );

        $pageTwo = $this->get(route('categories.subcategories.create', ['page' => 2, 'per_page' => 10]));
        $pageTwo->assertOk();
        $pageTwo->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Categories/CreateSubcategory', false)
            ->where('pagination.currentPage', 2)
            ->where('pagination.from', 11)
            ->where('pagination.to', 19)
        );
    }
}
