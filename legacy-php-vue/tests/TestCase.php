<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Map of UserRole enum value -> Spatie role name seeded by RolePermissionSeeder.
     */
    protected const ROLE_MAP = [
        UserRole::Administrator->value => 'administrator',
        UserRole::Manager->value => 'manager',
        UserRole::Cashier->value => 'cashier',
        UserRole::WarehouseStaff->value => 'warehouse_staff',
        UserRole::PurchasingStaff->value => 'purchasing_staff',
        UserRole::DeliveryStaff->value => 'delivery_staff',
        UserRole::Salesperson->value => 'sales_staff',
    ];

    /**
     * Run the role/permission seeder so spatie roles and permissions exist.
     * Idempotent — safe to call from every test setUp().
     */
    protected function seedRolesAndPermissions(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class]);
    }

    /**
     * Create a User with the given enum role and assign the matching spatie role.
     * Returns the created User so tests can use it for actingAs / assertions.
     */
    protected function createUserWithRole(string $roleEnumValue, array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['role' => $roleEnumValue], $attrs));

        $spatieRole = self::ROLE_MAP[$roleEnumValue] ?? null;
        if ($spatieRole !== null) {
            $user->assignRole($spatieRole);
        }

        return $user;
    }

    /**
     * Convenience helper: seed roles, create a user, return a bearer token.
     * Use with ->withHeader('Authorization', 'Bearer '.$token).
     */
    protected function tokenForRole(string $roleEnumValue, array $attrs = []): string
    {
        $this->seedRolesAndPermissions();

        return $this->createUserWithRole($roleEnumValue, $attrs)
            ->createToken('test')
            ->plainTextToken;
    }
}
