<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * CF4-84 — Admin asignación de clasificaciones (subcategoría).
 */
class ProductClassificationAdminTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Test',
            'first_surname' => 'Admin',
            'second_surname' => null,
            'gmail' => 'cf84-classif-admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    /** @return array{Category, Category, ClassificationDimension, ClassificationDimension, ClassificationValue, ClassificationValue, ClassificationValue} */
    private function seedSubcategoryWithTwoDimensions(): array
    {
        $root = Category::create([
            'name' => 'CF84 Admin Root '.uniqid(),
            'description' => null,
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'CF84 Sub '.uniqid(),
            'description' => null,
            'parent_category_id' => $root->category_id,
        ]);

        $dimColor = ClassificationDimension::create([
            'category_id' => $sub->category_id,
            'slug' => 'color',
            'label' => 'Color',
            'sort_order' => 0,
        ]);
        $dimSize = ClassificationDimension::create([
            'category_id' => $sub->category_id,
            'slug' => 'size',
            'label' => 'Talla',
            'sort_order' => 1,
        ]);

        $vRed = ClassificationValue::create([
            'classification_dimension_id' => $dimColor->id,
            'value' => 'Rojo',
            'normalized_value' => ClassificationValue::normalizeStoredValue('Rojo'),
            'sort_order' => 0,
        ]);
        $vBlue = ClassificationValue::create([
            'classification_dimension_id' => $dimColor->id,
            'value' => 'Azul',
            'normalized_value' => ClassificationValue::normalizeStoredValue('Azul'),
            'sort_order' => 1,
        ]);
        $vM = ClassificationValue::create([
            'classification_dimension_id' => $dimSize->id,
            'value' => 'M',
            'normalized_value' => ClassificationValue::normalizeStoredValue('M'),
            'sort_order' => 0,
        ]);

        return [$root, $sub, $dimColor, $dimSize, $vRed, $vBlue, $vM];
    }

    public function test_guest_is_redirected_from_product_classifications_index(): void
    {
        $response = $this->get(route('admin.product-classifications.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_product_classifications_index(): void
    {
        $admin = $this->makeAdmin();
        Auth::guard('admin')->login($admin);

        [, $sub] = $this->seedSubcategoryWithTwoDimensions();

        Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'Producto CF84 List',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $response = $this->get(route('admin.product-classifications.index'));

        $response->assertOk();
        $response->assertSee('Producto CF84 List', false);
    }

    public function test_admin_can_assign_classifications_via_dedicated_form(): void
    {
        $admin = $this->makeAdmin();
        Auth::guard('admin')->login($admin);

        [, $sub, , , $vRed, , $vM] = $this->seedSubcategoryWithTwoDimensions();

        $product = Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'Producto CF84 Assign',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $response = $this->put(route('admin.products.classifications.update', $product), [
            'classification_value_ids' => [$vRed->id, $vM->id],
        ]);

        $response->assertRedirect(route('admin.product-classifications.index'));
        $response->assertSessionHas('status');

        $product->refresh();
        $this->assertCount(2, $product->classificationValues);
        $this->assertTrue($product->classificationValues->pluck('id')->contains($vRed->id));
        $this->assertTrue($product->classificationValues->pluck('id')->contains($vM->id));
    }

    public function test_assign_rejects_two_values_same_dimension(): void
    {
        $admin = $this->makeAdmin();
        Auth::guard('admin')->login($admin);

        [, $sub, , , $vRed, $vBlue] = $this->seedSubcategoryWithTwoDimensions();

        $product = Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'Producto CF84 Dup',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $response = $this->from(route('admin.products.classifications.edit', $product))
            ->put(route('admin.products.classifications.update', $product), [
                'classification_value_ids' => [$vRed->id, $vBlue->id],
            ]);

        $response->assertSessionHasErrors('classification_value_ids');
    }
}
