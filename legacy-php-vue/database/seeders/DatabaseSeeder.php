<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // RolePermissionSeeder MUST run before RoleSeeder so the role rows
            // exist when RoleSeeder calls $user->assignRole($user->role->value).
            RolePermissionSeeder::class,
            RoleSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            WarehouseSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
