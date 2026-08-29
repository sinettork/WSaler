<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Beverages'],
            ['name' => 'Snacks'],
            ['name' => 'Dairy'],
            ['name' => 'Bakery'],
            ['name' => 'Household'],
            ['name' => 'Personal Care'],
            ['name' => 'Electronics'],
            ['name' => 'Stationery'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => null,
                'parent_id' => null,
                'is_active' => true,
            ]);
        }
    }
}
