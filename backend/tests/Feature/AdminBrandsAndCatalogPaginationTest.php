<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminBrandsAndCatalogPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function loginAdmin(): AdminUser
    {
        $admin = AdminUser::create([
            'name' => 'Pager',
            'first_surname' => 'Admin',
            'second_surname' => null,
            'gmail' => 'admin-pager-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
        Auth::guard('admin')->login($admin);

        return $admin;
    }

    public function test_brands_index_renders_centered_shared_pagination(): void
    {
        $this->loginAdmin();

        for ($i = 0; $i < 12; $i++) {
            Brand::create(['name' => 'Marca paginación '.$i.' '.uniqid()]);
        }

        $pageOne = $this->get(route('brands.index', ['per_page' => 10]));
        $pageOne->assertOk();
        $pageOne->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Brands/Index', false)
            ->where('pagination.currentPage', 1)
            ->where('pagination.perPage', 10)
            ->where('pagination.total', 12)
        );

        $pageTwo = $this->get(route('brands.index', ['per_page' => 10, 'page' => 2]));
        $pageTwo->assertOk();
        $pageTwo->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Brands/Index', false)
            ->where('pagination.currentPage', 2)
        );
    }

    public function test_classification_catalog_index_is_paginated(): void
    {
        $this->loginAdmin();

        $root = Category::create([
            'name' => 'Raíz paginación '.uniqid(),
            'description' => null,
            'parent_category_id' => null,
        ]);

        for ($i = 0; $i < 12; $i++) {
            Category::create([
                'name' => 'Sub opciones '.$i.' '.uniqid(),
                'description' => null,
                'parent_category_id' => $root->category_id,
            ]);
        }

        $pageOne = $this->get(route('admin.classifications.catalog.index', ['per_page' => 10]));
        $pageOne->assertOk();
        $pageOne->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Classifications/Index', false)
            ->where('pagination.currentPage', 1)
            ->where('pagination.perPage', 10)
            ->where('pagination.total', 12)
        );

        $pageTwo = $this->get(route('admin.classifications.catalog.index', ['per_page' => 10, 'page' => 2]));
        $pageTwo->assertOk();
        $pageTwo->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Classifications/Index', false)
            ->where('pagination.currentPage', 2)
        );
    }
}
