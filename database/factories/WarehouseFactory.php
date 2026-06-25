<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    protected static int $counter = 1;

    public function definition(): array
    {
        $index = self::$counter++;

        return [
            'name' => 'Warehouse ' . $index,
            'code' => 'WH-' . str_pad($index, 3, '0', STR_PAD_LEFT),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
