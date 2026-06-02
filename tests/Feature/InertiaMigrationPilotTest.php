<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\FavoriteProduct;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaMigrationPilotTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_is_rendered_by_inertia(): void
    {
        $this->get(route('clients.legal.terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Legal/Terms', false)
                ->where('legalTitle', 'Términos y condiciones')
                ->where('legalUpdated', 'mayo 2026')
            );
    }

    public function test_client_home_page_is_rendered_by_inertia_for_guests(): void
    {
        $this->withSession([
            'cart' => [
                ['product_id' => 10, 'quantity' => 2],
                ['product_id' => 20, 'quantity' => 1],
            ],
        ])
            ->get(route('clients.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Home/Index', false)
                ->has('featuredProducts')
                ->has('categories')
                ->has('hero')
                ->where('showGuestRegisterCta', true)
                ->where('auth.client', null)
                ->where('cartCount', 3)
            );
    }

    public function test_client_home_page_shares_authenticated_client_props(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Inertia',
            'second_surname' => null,
            'gmail' => 'cliente-inertia@example.com',
            'password' => bcrypt('password'),
            'email_verified' => true,
            'active' => true,
        ]);

        $this->actingAs($client, 'clients')
            ->get(route('clients.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Home/Index', false)
                ->where('auth.client.gmail', 'cliente-inertia@example.com')
                ->where('showGuestRegisterCta', false)
                ->has('cartCount')
            );
    }

    public function test_client_home_page_serializes_featured_products_and_categories(): void
    {
        Cache::flush();

        $parent = Category::create([
            'name' => 'Bicicletas',
            'description' => 'Familia principal de bicicletas para ruta, montaña y ciudad.',
            'parent_category_id' => null,
        ]);
        $child = Category::create([
            'name' => 'MTB',
            'description' => 'Montaña',
            'parent_category_id' => $parent->category_id,
        ]);
        $supplier = Supplier::create([
            'name' => 'Proveedor Home',
            'primary_contact' => 'Contacto Home',
            'phone' => '88888888',
            'email' => 'home-supplier@example.com',
            'address' => 'Tienda',
            'delivery_time' => 2,
            'rating' => 4.8,
            'status' => 'active',
        ]);

        Product::create([
            'category_id' => $child->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Bicicleta Trail Inertia',
            'sku' => 'HOME-TRAIL-1',
            'description' => 'Bicicleta destacada para validar payload de Home.',
            'purchase_price' => 100000,
            'sale_price' => 150000,
            'stock_current' => 7,
            'stock_minimum' => 2,
            'status' => 'active',
            'is_featured' => true,
        ]);

        $this->get(route('clients.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Home/Index', false)
                ->has('featuredProducts', 1)
                ->where('featuredProducts.0.name', 'Bicicleta Trail Inertia')
                ->where('featuredProducts.0.priceFormatted', '₡150.000')
                ->where('featuredProducts.0.stockLabel', 'En stock')
                ->where('featuredProducts.0.canBuy', true)
                ->where('featuredProducts.0.sku', 'HOME-TRAIL-1')
                ->has('featuredProducts.0.image.fallback')
                ->where('featuredProducts.0.image.usesPlaceholder', true)
                ->has('featuredProducts.0.image.placeholderIconClass')
                ->has('featuredProducts.0.reviews.avg')
                ->has('categories', 1)
                ->where('categories.0.name', 'Bicicletas')
                ->has('categories.0.iconClass')
                ->has('categories.0.children', 1)
                ->where('categories.0.children.0.name', 'MTB')
            );
    }

    public function test_client_catalog_page_serializes_filters_products_and_favorites(): void
    {
        Cache::flush();

        $category = Category::create([
            'name' => 'Accesorios Catalog',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $supplier = Supplier::create([
            'name' => 'Proveedor Catalog',
            'primary_contact' => 'Contacto Catalog',
            'phone' => '88887777',
            'email' => 'catalog-supplier@example.com',
            'address' => 'Tienda',
            'delivery_time' => 2,
            'rating' => 4.7,
            'status' => 'active',
        ]);
        $brand = Brand::create(['name' => 'Marca Catalog Inertia']);
        $product = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Producto Catalog Alpha',
            'sku' => 'CAT-ALPHA-1',
            'description' => 'Producto para probar catálogo Inertia.',
            'purchase_price' => 10000,
            'sale_price' => 25000,
            'stock_current' => 3,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $product->brands()->attach($brand->id);

        $this->get(route('clients.catalog', ['search' => 'Alpha', 'brand_id' => $brand->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('filters.search', 'Alpha')
                ->where('filters.brandId', $brand->id)
                ->where('products.0.name', 'Producto Catalog Alpha')
                ->where('products.0.brands.0.name', 'Marca Catalog Inertia')
                ->where('products.0.image.usesPlaceholder', true)
                ->where('favoriteProductIds', [])
            );

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Catalog',
            'second_surname' => null,
            'gmail' => 'cliente-catalog@example.com',
            'password' => bcrypt('password'),
            'email_verified' => true,
            'active' => true,
        ]);
        FavoriteProduct::create([
            'user_id' => $client->user_id,
            'product_id' => $product->product_id,
        ]);

        $this->actingAs($client, 'clients')
            ->get(route('clients.catalog', ['search' => 'Alpha']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('products.0.isFavorite', true)
                ->where('favoriteProductIds.0', $product->product_id)
            );
    }

    public function test_admin_dashboard_inertia_pilot_requires_admin_auth(): void
    {
        $this->get(route('dashboard.inertia-pilot'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_dashboard_inertia_pilot_renders_for_admin(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Inertia',
            'second_surname' => null,
            'gmail' => 'admin-inertia@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('dashboard.inertia-pilot'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/Index', false)
                ->has('totalProducts')
                ->has('todaySales')
            );
    }
}
