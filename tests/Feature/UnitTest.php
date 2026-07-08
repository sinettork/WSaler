<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class]);
    }

    protected function actingAsRole(string $role): string
    {
        $user = User::factory()->create(['role' => $role]);
        $spatieRole = match ($role) {
            'admin' => 'administrator',
            'manager' => 'manager',
            'cashier' => 'cashier',
            'warehouse' => 'warehouse_staff',
            'purchasing' => 'purchasing_staff',
            'delivery' => 'delivery_staff',
            'salesperson' => 'sales_staff',
            default => 'cashier',
        };
        $user->assignRole($spatieRole);
        return $user->createToken('test')->plainTextToken;
    }

    public function test_admin_can_list_units(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/units')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_base_unit(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/units', [
                'name' => 'Kilogram',
                'short_code' => 'kg',
                'base' => true,
                'conversion_factor_to_base' => 99, // Should be overridden to 1
            ])
            ->assertCreated()
            ->assertJsonPath('data.conversion_factor_to_base', '1.0000');
    }

    public function test_admin_can_create_derived_unit(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/units', [
                'name' => 'Carton',
                'short_code' => 'ctn',
                'base' => false,
                'conversion_factor_to_base' => 24,
            ])
            ->assertCreated()
            ->assertJsonPath('data.short_code', 'ctn');
    }

    public function test_short_code_must_be_unique(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/units', [
                'name' => 'Another Piece',
                'short_code' => 'pcs',
                'base' => true,
                'conversion_factor_to_base' => 1,
            ])
            ->assertStatus(422);
    }

    public function test_cashier_cannot_create_unit(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/units', [
                'name' => 'Test',
                'short_code' => 'tst',
                'base' => true,
                'conversion_factor_to_base' => 1,
            ])
            ->assertForbidden();
    }
}
