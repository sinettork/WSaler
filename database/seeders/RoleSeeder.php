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
            // updateOrCreate so the seeder can be re-run after a partial wipe
            // (e.g. when only the roles/permissions tables were lost and users
            // already exist). Plain User::create() would blow up on the unique
            // email index and block the rest of the role assignments.
            $created = User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'role' => $user['role'],
                ]
            );

            // Link the user to the matching Role row in model_has_roles.
            // Without this, users.role is set but HasPermissions::hasPermissionTo()
            // finds no roles attached → 403 on every protected endpoint.
            // Requires RolePermissionSeeder to have run first.
            $created->assignRole($user['role']->value);
        }
    }
}
