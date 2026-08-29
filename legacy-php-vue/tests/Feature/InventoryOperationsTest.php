<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseReceipt;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_purchase_receipt_and_increase_stock(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $product = Product::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/purchase-receipts', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'reference_number' => 'PO-1001',
                'received_at' => now()->toDateTimeString(),
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 4.5,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.reference_number', 'PO-1001');

        $this->assertDatabaseHas('batches', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'remaining_quantity' => 10,
        ]);
    }

    public function test_can_create_refund_and_restore_stock(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-002', 'is_default' => false, 'is_active' => true]);
        $sale = Sale::create([
            'invoice_number' => 'INV-TEST-001',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            'total' => 100,
            'paid' => 100,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson('/api/refunds', [
                'sale_id' => $sale->id,
                'customer_id' => $customer->id,
                'reason' => 'Damaged item',
                'refund_amount' => 25,
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_cost' => 12.5,
                ]],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('refunds', [
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);
    }

    public function test_can_transfer_stock_between_warehouses(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $product = Product::factory()->create();
        $sourceWarehouse = Warehouse::create(['name' => 'WH-A', 'code' => 'WH-A', 'is_default' => false, 'is_active' => true]);
        $destinationWarehouse = Warehouse::create(['name' => 'WH-B', 'code' => 'WH-B', 'is_default' => false, 'is_active' => true]);
        $batch = Batch::create([
            'batch_number' => 'BATCH-TRANSFER-001',
            'product_id' => $product->id,
            'warehouse_id' => $sourceWarehouse->id,
            'quantity' => 20,
            'remaining_quantity' => 20,
            'purchase_cost' => 3.5,
            'received_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->postJson('/api/stock-transfers', [
                'source_warehouse_id' => $sourceWarehouse->id,
                'destination_warehouse_id' => $destinationWarehouse->id,
                'reference_number' => 'TR-001',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 8,
                ]],
            ])
            ->assertCreated();

        $batch->refresh();
        $this->assertSame(12, $batch->remaining_quantity);
        $this->assertDatabaseHas('stock_movements', ['type' => 'stock_transfer_out', 'warehouse_id' => $sourceWarehouse->id]);
        $this->assertDatabaseHas('stock_movements', ['type' => 'stock_transfer_in', 'warehouse_id' => $destinationWarehouse->id]);
    }

    public function test_can_create_stock_adjustment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $product = Product::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-003', 'is_default' => false, 'is_active' => true]);
        $batch = Batch::create([
            'batch_number' => 'BATCH-ADJ-001',
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
            'remaining_quantity' => 15,
            'purchase_cost' => 2.25,
            'received_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->postJson('/api/stock-adjustments', [
                'warehouse_id' => $warehouse->id,
                'reason' => 'Damaged stock',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'type' => 'decrease',
                ]],
            ])
            ->assertCreated();

        $batch->refresh();
        $this->assertSame(12, $batch->remaining_quantity);
        $this->assertDatabaseHas('stock_adjustments', ['warehouse_id' => $warehouse->id, 'reason' => 'Damaged stock']);
    }

    public function test_can_list_inventory_operations(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-004', 'is_default' => true, 'is_active' => true]);
        $supplier = Supplier::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        PurchaseReceipt::create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
            'reference_number' => 'PR-LIST-001',
            'status' => 'completed',
            'received_at' => now(),
        ]);

        Refund::create([
            'sale_id' => Sale::create([
                'invoice_number' => 'INV-LIST-001',
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $admin->id,
                'subtotal' => 50,
                'discount' => 0,
                'tax' => 0,
                'total' => 50,
                'paid' => 50,
                'change_due' => 0,
                'status' => 'completed',
                'sold_at' => now(),
            ])->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
            'reference_number' => 'REF-LIST-001',
            'refund_amount' => 10,
            'reason' => 'Test refund',
            'status' => 'completed',
            'refunded_at' => now(),
        ]);

        StockTransfer::create([
            'source_warehouse_id' => $warehouse->id,
            'destination_warehouse_id' => Warehouse::create(['name' => 'Backup', 'code' => 'WH-005', 'is_default' => false, 'is_active' => true])->id,
            'user_id' => $admin->id,
            'reference_number' => 'TR-LIST-001',
            'status' => 'completed',
            'transferred_at' => now(),
        ]);

        StockAdjustment::create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
            'reference_number' => 'ADJ-LIST-001',
            'reason' => 'Cycle count',
            'status' => 'completed',
            'adjusted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/purchase-receipts')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->actingAs($admin)
            ->getJson('/api/refunds')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->actingAs($admin)
            ->getJson('/api/stock-transfers')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->actingAs($admin)
            ->getJson('/api/stock-adjustments')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }
}
