<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Coca-Cola'],
            ['name' => 'PepsiCo'],
            ['name' => 'Nestlé'],
            ['name' => 'Unilever'],
            ['name' => 'P&G'],
            ['name' => 'Samsung'],
        ];

        foreach ($brands as $brand) {
            Brand::create([
                'name' => $brand['name'],
                'slug' => Str::slug($brand['name']),
                'description' => null,
                'logo' => null,
                'is_active' => true,
            ]);
        }
    }
}
