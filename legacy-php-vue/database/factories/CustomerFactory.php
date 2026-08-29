<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = \App\Models\Customer::class;

    public function definition(): array
    {
        return [
            'code' => 'CUST-' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'type' => $this->faker->randomElement(['retail', 'wholesale', 'distributor', 'vip']),
            'credit_limit' => $this->faker->randomFloat(2, 0, 50000),
            'current_balance' => $this->faker->randomFloat(2, 0, 10000),
            'payment_terms' => $this->faker->optional()->sentence(),
            'notes' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
