<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    protected static int $counter = 1;

    public function definition(): array
    {
        $index = self::$counter++;

        return [
            'name' => 'Unit ' . $index,
            'short_code' => 'u' . $index,
            'base' => true,
            'conversion_factor_to_base' => 1,
        ];
    }
}
