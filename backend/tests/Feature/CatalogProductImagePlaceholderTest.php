<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogProductImagePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_category_icon_placeholder_for_product_without_image(): void
    {
        $root = Category::create([
            'name' => 'Bicicletas',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'MTB',
            'parent_category_id' => $root->category_id,
        ]);
        Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'Bike Sin Foto Catalog',
            'description' => 'Test',
            'image' => 'default.png',
            'sale_price' => 100000,
            'purchase_price' => 50000,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => true,
        ]);

        $this->get(route('clients.catalog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('products.0.name', 'Bike Sin Foto Catalog')
                ->where('products.0.image.usesPlaceholder', true)
                ->where('products.0.image.placeholderIconClass', 'fas fa-bicycle')
            );
    }

    public function test_catalog_shows_placeholder_when_media_file_is_missing(): void
    {
        Storage::fake('public');

        $root = Category::create([
            'name' => 'Seguridad',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'Candados',
            'parent_category_id' => $root->category_id,
        ]);
        $product = Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'Candado Sin Archivo',
            'description' => 'Test',
            'image' => 'default.png',
            'sale_price' => 55000,
            'purchase_price' => 35000,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $media = $product->addMediaFromString('not-a-real-image', 'orphan.jpg')
            ->toMediaCollection('main_image');
        Storage::disk($media->disk)->delete($media->getPathRelativeToRoot());

        $this->get(route('clients.catalog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('products.0.name', 'Candado Sin Archivo')
                ->where('products.0.image.usesPlaceholder', true)
                ->where('products.0.image.placeholderIconClass', 'fas fa-lock')
            );
    }
}
