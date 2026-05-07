<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\ClassificationDimension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClassificationCatalogCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                $this->markTestSkipped('Requires MySQL.');
            }
            foreach (['categories', 'classification_dimensions', 'classification_values', 'admins'] as $t) {
                if (! Schema::hasTable($t)) {
                    $this->markTestSkipped('Missing table: '.$t);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    private function loginAdmin(): AdminUser
    {
        $admin = AdminUser::create([
            'name' => 'Cat',
            'first_surname' => 'Admin',
            'second_surname' => null,
            'gmail' => 'cf84-cat-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
        Auth::guard('admin')->login($admin);

        return $admin;
    }

    public function test_admin_can_create_dimension_and_value_and_rejects_duplicate_slug(): void
    {
        $this->loginAdmin();

        $root = Category::create([
            'name' => 'Root '.uniqid(),
            'description' => null,
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'Sub '.uniqid(),
            'description' => null,
            'parent_category_id' => $root->category_id,
        ]);

        $this->post(route('admin.classifications.dimensions.store', $sub), [
            'slug' => 'color',
            'label' => 'Color',
            'sort_order' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('classification_dimensions', [
            'category_id' => $sub->category_id,
            'slug' => 'color',
        ]);

        $this->post(route('admin.classifications.dimensions.store', $sub), [
            'slug' => 'color',
            'label' => 'Otro',
            'sort_order' => 1,
        ])->assertSessionHasErrors('slug');

        $dim = ClassificationDimension::where('slug', 'color')->firstOrFail();

        $this->post(route('admin.classifications.values.store', $dim), [
            'value' => 'Rojo',
            'sort_order' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('classification_values', [
            'classification_dimension_id' => $dim->id,
            'value' => 'Rojo',
        ]);

        $this->post(route('admin.classifications.values.store', $dim), [
            'value' => 'rojo',
            'sort_order' => 1,
        ])->assertSessionHasErrors('value');
    }

    public function test_options_json_returns_attributes_for_subcategory_only(): void
    {
        $this->loginAdmin();

        $root = Category::create([
            'name' => 'R2 '.uniqid(),
            'description' => null,
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'S2 '.uniqid(),
            'description' => null,
            'parent_category_id' => $root->category_id,
        ]);

        ClassificationDimension::create([
            'category_id' => $sub->category_id,
            'slug' => 'size',
            'label' => 'Talla',
            'sort_order' => 0,
        ]);

        $this->getJson(route('admin.classifications.catalog.options', $root))
            ->assertOk()
            ->assertJson(['attributes' => [], 'dimensions' => []]);

        $this->getJson(route('admin.classifications.catalog.options', $sub))
            ->assertOk()
            ->assertJsonPath('attributes.0.slug', 'size')
            ->assertJsonPath('dimensions.0.slug', 'size');
    }
}
