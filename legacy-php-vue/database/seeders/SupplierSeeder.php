<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'ABC Distributors'],
            ['name' => 'XYZ Wholesale'],
            ['name' => 'Mega Supplies'],
            ['name' => 'Prime Goods Co'],
            ['name' => 'Global Traders'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create([
                'name' => $supplier['name'],
                'contact_person' => null,
                'email' => null,
                'phone' => null,
                'address' => null,
                'tax_number' => null,
                'payment_terms' => null,
                'notes' => null,
                'is_active' => true,
            ]);
        }
    }
}
