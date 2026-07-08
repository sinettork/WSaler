<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTest extends TestCase
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

    public function test_admin_can_list_warehouses(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/warehouses')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_warehouse_with_auto_code(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/warehouses', [
                'name' => 'Branch',
                'address' => '123 Main St',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'WH-001');
    }

    public function test_only_one_default_warehouse(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $w1 = Warehouse::create(['name' => 'First', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/warehouses', [
                'name' => 'Second',
                'is_default' => true,
                'is_active' => true,
            ])
            ->assertCreated();

        $this->assertFalse($w1->fresh()->is_default);
        $this->assertEquals(1, Warehouse::where('is_default', true)->count());
    }

    public function test_cannot_delete_default_warehouse(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $w = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/warehouses/{$w->id}")
            ->assertStatus(422);
    }

    public function test_cashier_cannot_create_warehouse(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/warehouses', [
                'name' => 'Test',
                'is_active' => true,
            ])
            ->assertForbidden();
    }
}
