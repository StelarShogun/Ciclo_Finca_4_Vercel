<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CF4-84 — Validates Eloquent relations, pivot integrity, soft deletes, and query efficiency (eager vs N+1).
 * Admin UI is not required here; these tests protect future catalog/API payloads and list endpoints.
 */
class ClassificationEloquentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Category, 1: Category, 2: ClassificationDimension, 3: ClassificationValue} */
    private function seedCategoryDimensionAndColorValue(): array
    {
        $root = Category::create([
            'name' => 'CF84 Root '.uniqid(),
            'description' => null,
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'CF84 Sub '.uniqid(),
            'description' => null,
            'parent_category_id' => $root->category_id,
        ]);
        $dimension = ClassificationDimension::create([
            'category_id' => $sub->category_id,
            'slug' => 'color',
            'label' => 'Color',
            'sort_order' => 0,
        ]);
        $value = ClassificationValue::create([
            'classification_dimension_id' => $dimension->id,
            'value' => 'Red',
            'normalized_value' => ClassificationValue::normalizeStoredValue('Red'),
            'sort_order' => 0,
        ]);

        return [$root, $sub, $dimension, $value];
    }

    public function test_pivot_stores_classification_dimension_id_and_product_resolves_values(): void
    {
        [, $sub, $dimension, $value] = $this->seedCategoryDimensionAndColorValue();

        $product = Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'SKU CF84',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $product->classificationValues()->attach($value->id, [
            'classification_dimension_id' => $dimension->id,
        ]);

        $product->refresh();
        $loaded = $product->classificationValues;
        $this->assertCount(1, $loaded);
        $this->assertSame($value->id, $loaded->first()->id);
        $pivot = $loaded->first()->getRelationValue('pivot');
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertSame($dimension->id, (int) $pivot->getAttribute('classification_dimension_id'));

        $this->assertTrue(
            $sub->classificationDimensions()->whereKey($dimension->id)->exists()
        );
    }

    public function test_eager_loading_bounded_queries_vs_lazy_loading_n_plus_one(): void
    {
        [, $sub, $dimension, $value] = $this->seedCategoryDimensionAndColorValue();

        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $p = Product::create([
                'category_id' => $sub->category_id,
                'supplier_id' => null,
                'name' => 'Bulk '.$i,
                'description' => null,
                'image' => 'default.png',
                'sale_price' => 10,
                'purchase_price' => 1,
                'stock_current' => 1,
                'stock_minimum' => 1,
                'status' => 'active',
            ]);
            $p->classificationValues()->attach($value->id, [
                'classification_dimension_id' => $dimension->id,
            ]);
            $ids[] = $p->product_id;
        }

        DB::connection()->enableQueryLog();
        $products = Product::query()
            ->whereIn('product_id', $ids)
            ->with('classificationValues')
            ->orderBy('product_id')
            ->get();
        foreach ($products as $p) {
            $this->assertCount(1, $p->classificationValues);
        }
        $eagerCount = count(DB::getQueryLog());
        DB::connection()->disableQueryLog();

        $this->assertLessThanOrEqual(
            6,
            $eagerCount,
            'Eager loading should use a small constant number of queries, not scale with row count.'
        );

        DB::connection()->enableQueryLog();
        $productsLazy = Product::query()->whereIn('product_id', $ids)->orderBy('product_id')->get();
        foreach ($productsLazy as $p) {
            $p->classificationValues->count();
        }
        $lazyCount = count(DB::getQueryLog());
        DB::connection()->disableQueryLog();

        $this->assertGreaterThan(
            $eagerCount,
            $lazyCount,
            'Lazy-loading relations in a loop should issue more queries than a single eager load (N+1 pattern).'
        );
    }

    public function test_soft_deleted_dimension_is_excluded_from_default_query(): void
    {
        [, $sub, $dimension, $value] = $this->seedCategoryDimensionAndColorValue();

        $this->assertTrue(ClassificationDimension::query()->whereKey($dimension->id)->exists());
        $dimension->delete();
        $this->assertFalse(ClassificationDimension::query()->whereKey($dimension->id)->exists());
        $this->assertTrue(ClassificationDimension::withTrashed()->whereKey($dimension->id)->exists());
    }

    public function test_scope_for_category_limits_dimensions(): void
    {
        [, $sub, $dimension] = $this->seedCategoryDimensionAndColorValue();

        $other = Category::create([
            'name' => 'Other '.uniqid(),
            'description' => null,
            'parent_category_id' => null,
        ]);

        ClassificationDimension::create([
            'category_id' => $other->category_id,
            'slug' => 'size',
            'label' => 'Size',
            'sort_order' => 0,
        ]);

        $scoped = ClassificationDimension::query()->forCategory($sub->category_id)->get();
        $this->assertCount(1, $scoped);
        $this->assertSame($dimension->id, $scoped->first()->id);
    }

    public function test_payload_shape_for_future_api_or_client_lists(): void
    {
        [, $sub, $dimension, $value] = $this->seedCategoryDimensionAndColorValue();

        $product = Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'API shape',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 50,
            'purchase_price' => 5,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
        $product->classificationValues()->attach($value->id, [
            'classification_dimension_id' => $dimension->id,
        ]);

        $product->load(['classificationValues.dimension']);
        $arr = $product->toArray();
        $this->assertArrayHasKey('classification_values', $arr);
        $this->assertIsArray($arr['classification_values']);
        $this->assertNotEmpty($arr['classification_values']);
        $first = $arr['classification_values'][0];
        $this->assertArrayHasKey('value', $first);
        $this->assertArrayHasKey('pivot', $first);
        $this->assertArrayHasKey('classification_dimension_id', $first['pivot']);
    }
}
