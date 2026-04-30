<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Models\AdminUser;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-66 — Creación de categorías padre desde el panel admin.
 *
 * Cubre CP-01 a CP-06 definidos en la historia de usuario.
 */
class AdminParentCategoryCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                $this->markTestSkipped('Requires MySQL.');
            }

            foreach (['categories', 'admins'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Missing table: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped($e->getMessage());
        }

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

    /** CP-01: enviar el formulario sin nombre rechaza la creación (CA1). */
    public function test_cp01_rejects_empty_name(): void
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

    /** CP-02: nombre presente y descripción vacía crea la categoría (CA2). */
    public function test_cp02_allows_empty_description(): void
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

    /** CP-03: un nombre duplicado entre categorías padre es rechazado (CA3). */
    public function test_cp03_rejects_duplicate_parent_name(): void
    {
        $this->loginAdmin();

        $name = 'Categoría CP03 '.uniqid();

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

        // Still a single parent row with that name.
        $this->assertSame(
            1,
            Category::whereNull('parent_category_id')->where('name', $name)->count()
        );
    }

    /** CP-04: creación exitosa persiste la categoría y muestra el mensaje de éxito (CA4). */
    public function test_cp04_creates_parent_and_flashes_status(): void
    {
        $this->loginAdmin();

        $name = 'Categoría CP04 '.uniqid();

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

    /** CP-05: la categoría creada aparece en el selector al crear subcategoría (CA5). */
    public function test_cp05_new_parent_appears_in_subcategory_form_selector(): void
    {
        $this->loginAdmin();

        $name = 'Categoría CP05 '.uniqid();

        $this->post(route('categories.parents.store'), [
            'name' => $name,
            'description' => null,
        ])->assertRedirect(route('categories.parents.create'));

        $response = $this->get(route('categories.subcategories.create'));
        $response->assertOk();

        // The option should show up with the new parent name on the subcategory form.
        $response->assertSee($name, false);

        $created = Category::whereNull('parent_category_id')->where('name', $name)->firstOrFail();
        $response->assertSee('value="'.$created->category_id.'"', false);
    }

    /** CP-06: permitir el mismo nombre si el choque es contra una subcategoría (la unicidad solo aplica a padres). */
    public function test_cp06_allows_parent_name_matching_existing_subcategory(): void
    {
        $this->loginAdmin();

        $parent = Category::create([
            'name' => 'Otro padre '.uniqid(),
            'description' => null,
            'parent_category_id' => null,
        ]);

        $sharedName = 'Compartido '.uniqid();

        Category::create([
            'name' => $sharedName,
            'description' => null,
            'parent_category_id' => $parent->category_id,
        ]);

        $response = $this->post(route('categories.parents.store'), [
            'name' => $sharedName,
            'description' => null,
        ]);

        $response->assertRedirect(route('categories.parents.create'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('categories', [
            'name' => $sharedName,
            'parent_category_id' => null,
        ]);
    }
}
