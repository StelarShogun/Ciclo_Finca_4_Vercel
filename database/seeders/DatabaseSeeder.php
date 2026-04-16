<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            CategorySeeder::class,
            ClassificationCatalogSeeder::class,
            SupplierSeeder::class,
            BrandSeeder::class,
            ProductsSeeder::class,
            ProductClassificationDemoSeeder::class,
            ClientUserSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
