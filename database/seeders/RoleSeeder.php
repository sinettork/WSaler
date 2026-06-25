<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => 'password', 'role' => UserRole::Administrator],
            ['name' => 'Manager User', 'email' => 'manager@example.com', 'password' => 'password', 'role' => UserRole::Manager],
            ['name' => 'Cashier User', 'email' => 'cashier@example.com', 'password' => 'password', 'role' => UserRole::Cashier],
            ['name' => 'Warehouse User', 'email' => 'warehouse@example.com', 'password' => 'password', 'role' => UserRole::WarehouseStaff],
            ['name' => 'Purchasing User', 'email' => 'purchasing@example.com', 'password' => 'password', 'role' => UserRole::PurchasingStaff],
            ['name' => 'Delivery User', 'email' => 'delivery@example.com', 'password' => 'password', 'role' => UserRole::DeliveryStaff],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make($user['password']),
                'role' => $user['role'],
            ]);
        }
    }
}
