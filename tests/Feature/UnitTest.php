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

    public function test_admin_can_list_units(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);

        $this->actingAs($admin)
            ->getJson('/api/units')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_base_unit(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);

        $this->actingAs($admin)
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
        $admin = User::factory()->create(['role' => UserRole::Administrator]);

        $this->actingAs($admin)
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
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);

        $this->actingAs($admin)
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
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);

        $this->actingAs($cashier)
            ->postJson('/api/units', [
                'name' => 'Test',
                'short_code' => 'tst',
                'base' => true,
                'conversion_factor_to_base' => 1,
            ])
            ->assertForbidden();
    }
}
