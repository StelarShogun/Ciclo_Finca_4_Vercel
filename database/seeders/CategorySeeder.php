<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Main cycling categories
            ['name' => 'Bicycles', 'description' => 'Complete bicycles of different types', 'parent_category_id' => null],
            ['name' => 'Components', 'description' => 'Bicycle components and spare parts', 'parent_category_id' => null],
            ['name' => 'Accessories', 'description' => 'Cycling accessories and equipment', 'parent_category_id' => null],
            ['name' => 'Sportswear', 'description' => 'Specialized clothing for cycling', 'parent_category_id' => null],
            ['name' => 'Tools', 'description' => 'Bicycle maintenance tools', 'parent_category_id' => null],
            ['name' => 'Safety', 'description' => 'Helmets, lights and safety gear', 'parent_category_id' => null],
            ['name' => 'Nutrition', 'description' => 'Supplements and sports drinks', 'parent_category_id' => null],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}