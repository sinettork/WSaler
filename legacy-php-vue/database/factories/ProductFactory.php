<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    protected static int $unitCounter = 1;

    public function definition(): array
    {
        $unitIndex = self::$unitCounter++;

        return [
            'name' => $this->faker->unique()->words(3, true),
            'sku' => 'PRD-' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'barcode' => $this->faker->optional()->ean13(),
            'description' => $this->faker->optional()->sentence(),
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'base_unit_id' => Unit::factory()->state([
                'name' => 'Unit ' . $unitIndex,
                'short_code' => 'u' . $unitIndex,
                'base' => true,
                'conversion_factor_to_base' => 1,
            ]),
            'image' => null,
            'retail_price' => $this->faker->randomFloat(2, 1, 100),
            'wholesale_price' => $this->faker->randomFloat(2, 1, 80),
            'distributor_price' => $this->faker->randomFloat(2, 1, 60),
            'cost_price' => $this->faker->randomFloat(2, 1, 50),
            'status' => 'active',
            'track_stock' => true,
        ];
    }
}
