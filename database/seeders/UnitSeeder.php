<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1],
            ['name' => 'Box', 'short_code' => 'box', 'base' => false, 'conversion_factor_to_base' => 6],
            ['name' => 'Carton', 'short_code' => 'ctn', 'base' => false, 'conversion_factor_to_base' => 24],
            ['name' => 'Kilogram', 'short_code' => 'kg', 'base' => true, 'conversion_factor_to_base' => 1],
            ['name' => 'Liter', 'short_code' => 'l', 'base' => true, 'conversion_factor_to_base' => 1],
            ['name' => 'Pack', 'short_code' => 'pack', 'base' => false, 'conversion_factor_to_base' => 12],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}
