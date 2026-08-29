<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
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

    public function test_admin_can_list_suppliers(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        Supplier::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/suppliers');
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_purchasing_can_list_suppliers(): void
    {
        $token = $this->actingAsRole(UserRole::PurchasingStaff->value);
        Supplier::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/suppliers');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_supplier(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/suppliers', [
                'name' => 'New Supplier',
                'email' => 'supplier@example.com',
                'is_active' => true,
            ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Supplier');
        $this->assertDatabaseHas('suppliers', ['email' => 'supplier@example.com']);
    }

    public function test_cashier_cannot_create_supplier(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/suppliers', [
                'name' => 'Bad',
                'email' => 'bad@example.com',
            ]);
        $response->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/suppliers', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_show_supplier(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $supplier = Supplier::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/suppliers/'.$supplier->id);
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $supplier->id);
    }

    public function test_admin_can_update_supplier(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $supplier = Supplier::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/suppliers/'.$supplier->id, [
                'name' => 'Updated Supplier',
                'is_active' => false,
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Supplier');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Updated Supplier']);
    }

    public function test_admin_can_delete_supplier(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $supplier = Supplier::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/suppliers/'.$supplier->id);
        $response->assertStatus(204);
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }
}
