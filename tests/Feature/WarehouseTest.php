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

    public function test_admin_can_list_warehouses(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $this->actingAs($admin)
            ->getJson('/api/warehouses')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_warehouse_with_auto_code(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);

        $this->actingAs($admin)
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
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $w1 = Warehouse::create(['name' => 'First', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $this->actingAs($admin)
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
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $w = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $this->actingAs($admin)
            ->deleteJson("/api/warehouses/{$w->id}")
            ->assertStatus(422);
    }

    public function test_cashier_cannot_create_warehouse(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);

        $this->actingAs($cashier)
            ->postJson('/api/warehouses', [
                'name' => 'Test',
                'is_active' => true,
            ])
            ->assertForbidden();
    }
}
