<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Trek',
            'Specialized',
            'Giant',
            'Cannondale',
            'Scott',
            'Shimano',
            'SRAM',
            'Maxxis',
            'Kenda',
            'Park Tool',
            'Giro',
            'Fox',
        ];

        foreach ($brands as $name) {
            Brand::query()->firstOrCreate(['name' => $name]);
        }
    }
}
