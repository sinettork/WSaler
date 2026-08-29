<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Main Warehouse',
                'code' => 'WH-001',
                'address' => '123 Main St, Industrial Area',
                'phone' => '1234567890',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Branch Warehouse',
                'code' => 'WH-002',
                'address' => '456 Branch Rd, Downtown',
                'phone' => '0987654321',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Returns Warehouse',
                'code' => 'WH-003',
                'address' => '789 Returns Ave, Outskirts',
                'phone' => null,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
    }
}
