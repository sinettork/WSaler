<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    protected $model = Batch::class;

    protected static int $batchCounter = 1;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(10, 500);
        $batchNum = self::$batchCounter++;

        return [
            'batch_number' => 'BATCH-' . now()->format('ymd') . '-' . str_pad($batchNum, 4, '0', STR_PAD_LEFT),
            'product_id' => Product::factory(),
            'variation_id' => null,
            'warehouse_id' => Warehouse::factory()->state([
                'name' => 'Warehouse ' . $batchNum,
                'code' => 'WH-' . str_pad($batchNum, 3, '0', STR_PAD_LEFT),
                'is_default' => false,
                'is_active' => true,
            ]),
            'supplier_id' => Supplier::factory(),
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'reserved_quantity' => 0,
            'purchase_cost' => $this->faker->randomFloat(4, 1, 100),
            'manufacture_date' => $this->faker->optional()->date(),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+2 years'),
            'received_date' => $this->faker->date(),
            'notes' => $this->faker->optional()->sentence(),
            'status' => 'active',
        ];
    }
}
