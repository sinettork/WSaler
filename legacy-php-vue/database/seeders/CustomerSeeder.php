<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Walk-in Retail', 'type' => 'retail', 'credit_limit' => 0],
            ['name' => 'Corner Shop', 'type' => 'retail', 'credit_limit' => 1000],
            ['name' => 'Green Mart', 'type' => 'wholesale', 'credit_limit' => 10000],
            ['name' => 'City Distributor', 'type' => 'distributor', 'credit_limit' => 50000],
            ['name' => 'Sunrise Grocers', 'type' => 'wholesale', 'credit_limit' => 15000],
            ['name' => 'Elite VIP', 'type' => 'vip', 'credit_limit' => 100000],
            ['name' => 'Quick Stop', 'type' => 'retail', 'credit_limit' => 500],
            ['name' => 'Mega Plaza', 'type' => 'wholesale', 'credit_limit' => 25000],
        ];

        foreach ($customers as $index => $customer) {
            Customer::create([
                'code' => 'CUST-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'name' => $customer['name'],
                'contact_person' => null,
                'email' => null,
                'phone' => null,
                'address' => null,
                'type' => $customer['type'],
                'credit_limit' => $customer['credit_limit'],
                'current_balance' => 0,
                'payment_terms' => null,
                'notes' => null,
                'is_active' => true,
            ]);
        }
    }
}
