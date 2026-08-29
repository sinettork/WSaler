<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_warehouse_can_create_batch(): void
    {
        $user = $this->createUserWithRole(UserRole::WarehouseStaff->value);
        $product = Product::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);
        $supplier = Supplier::factory()->create();

        $payload = [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'quantity' => 100,
            'purchase_cost' => 5.50,
            'expiry_date' => Carbon::today()->addMonths(6)->format('Y-m-d'),
            'received_date' => Carbon::today()->format('Y-m-d'),
        ];

        $this->actingAs($user)
            ->postJson('/api/batches', $payload)
            ->assertCreated()
            ->assertJsonPath('data.batch_number', 'BATCH-' . Carbon::today()->format('ymd') . '-0001');

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'stock_in',
            'quantity' => 100,
        ]);
    }

    public function test_batch_number_auto_generates(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $product = Product::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $this->actingAs($admin)
            ->postJson('/api/batches', [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 50,
                'purchase_cost' => 3.00,
                'received_date' => Carbon::today()->format('Y-m-d'),
            ])
            ->assertCreated();

        $batch = Batch::first();
        $this->assertStringStartsWith('BATCH-' . Carbon::today()->format('ymd'), $batch->batch_number);
    }

    public function test_expiring_endpoint(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $product = Product::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        Batch::create([
            'batch_number' => 'B-NEAR',
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'purchase_cost' => 1,
            'expiry_date' => Carbon::today()->addDays(20),
            'received_date' => Carbon::today(),
            'status' => 'active',
        ]);

        Batch::create([
            'batch_number' => 'B-FAR',
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'purchase_cost' => 1,
            'expiry_date' => Carbon::today()->addDays(200),
            'received_date' => Carbon::today(),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/batches/expiring?days=30')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_expired_endpoint(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $product = Product::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        Batch::create([
            'batch_number' => 'B-OLD',
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'purchase_cost' => 1,
            'expiry_date' => Carbon::today()->subDays(5),
            'received_date' => Carbon::today()->subDays(100),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/batches/expired')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_cannot_delete_batch_with_consumed_stock(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $batch = Batch::factory()->create(['quantity' => 100, 'remaining_quantity' => 50, 'purchase_cost' => 5]);

        $this->actingAs($admin)
            ->deleteJson("/api/batches/{$batch->id}")
            ->assertStatus(422);
    }

    public function test_admin_can_delete_unused_batch(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $batch = Batch::factory()->create(['quantity' => 100, 'remaining_quantity' => 100, 'purchase_cost' => 5]);

        $this->actingAs($admin)
            ->deleteJson("/api/batches/{$batch->id}")
            ->assertNoContent();
    }

    public function test_admin_can_list_batches(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        Batch::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/batches')
            ->assertOk();
    }

    public function test_cashier_cannot_create_batch(): void
    {
        $cashier = $this->createUserWithRole(UserRole::Cashier->value);
        $product = Product::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $this->actingAs($cashier)
            ->postJson('/api/batches', [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 10,
                'purchase_cost' => 1,
                'received_date' => Carbon::today()->format('Y-m-d'),
            ])
            ->assertForbidden();
    }
}
