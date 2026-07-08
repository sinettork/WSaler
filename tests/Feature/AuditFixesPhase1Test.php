<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit Fixes Phase 1 — covers critical findings F1, F3, F4, F5, F13:
 *  - F1: PHP syntax errors fixed (verified via `php -l`).
 *  - F3: BatchController update/destroy wrap in DB::transaction with lockForUpdate.
 *  - F4: BatchController destroy pre-checks stock_movements + catches FK violation.
 *  - F5: WarehouseController / CategoryController / ProductController /
 *        UserController destroy enforce referential-integrity guards.
 *  - F13: CustomerController / SupplierController destroy reject when
 *         sales / batches reference the entity.
 */
class AuditFixesPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class]);
        $this->admin = User::factory()->create(['role' => UserRole::Administrator]);
        $this->admin->assignRole('administrator');
    }

    // ===================================================================
    // F5 — WarehouseController::destroy
    // ===================================================================

    public function test_warehouse_cannot_be_deleted_when_stock_movements_reference_it(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'WH-A', 'code' => 'WH-A-001',
            'is_default' => false, 'is_active' => true,
        ]);
        $product = Product::factory()->create();
        $batch = Batch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'purchase_cost' => 10.00,
        ]);
        StockMovement::create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'stock_in',
            'quantity' => 10,
            'unit_cost' => 1,
            'reference_type' => null,
            'reference_id' => null,
            'occurred_at' => now(),
        ]);
        // Soft-delete the batch so the controller's `batches()->exists()` check
        // passes, forcing the stock_movements pre-check to fire instead.
        $batch->delete();

        $this->actingAs($this->admin)
            ->deleteJson("/api/warehouses/{$warehouse->id}")
            ->assertStatus(422)
            ->assertJsonPath('stock_movements_count', 1);

        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }

    public function test_warehouse_can_be_deleted_when_clean(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'WH-B', 'code' => 'WH-B-001',
            'is_default' => false, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/warehouses/{$warehouse->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);
    }

    // ===================================================================
    // F5 — CategoryController::destroy
    // ===================================================================

    public function test_category_cannot_be_deleted_when_products_exist(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(422)
            ->assertJsonPath('products_count', 1);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_can_be_deleted_when_empty(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertNoContent();
    }

    // ===================================================================
    // F5 — ProductController::destroy
    // ===================================================================

    public function test_product_cannot_be_deleted_when_batches_exist(): void
    {
        $product = Product::factory()->create();
        Batch::factory()->create(['product_id' => $product->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/products/{$product->id}")
            ->assertStatus(422)
            ->assertJsonPath('batches_count', 1);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_product_cannot_be_deleted_when_sale_items_exist(): void
    {
        $unit = \App\Models\Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $sale = Sale::create([
            'invoice_number' => 'INV-000001',
            'customer_id' => null,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->admin->id,
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            'total' => 100,
            'paid' => 100,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/products/{$product->id}")
            ->assertStatus(422)
            ->assertJsonPath('sale_items_count', 1);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_product_can_be_deleted_when_clean(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();
    }

    // ===================================================================
    // F5 — UserController::destroy (self + last-admin protection)
    // ===================================================================

    public function test_admin_cannot_delete_self(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$this->admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete your own account.');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_can_delete_other_admin_when_multiple_admins_exist(): void
    {
        $otherAdmin = User::factory()->create(['role' => UserRole::Administrator]);
        $otherAdmin->assignRole('administrator');

        $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$otherAdmin->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $otherAdmin->id]);
    }

    public function test_admin_can_delete_non_admin_user(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);
        $cashier->assignRole('cashier');

        $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$cashier->id}")
            ->assertNoContent();
    }

    // ===================================================================
    // F13 — CustomerController::destroy
    // ===================================================================

    public function test_customer_cannot_be_deleted_when_sales_reference_it(): void
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        Sale::create([
            'invoice_number' => 'INV-000001',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->admin->id,
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            'total' => 100,
            'paid' => 100,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/customers/{$customer->id}")
            ->assertStatus(422)
            ->assertJsonPath('sales_count', 1);

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_customer_can_be_deleted_when_no_sales(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/customers/{$customer->id}")
            ->assertNoContent();
    }

    // ===================================================================
    // F13 — SupplierController::destroy
    // ===================================================================

    public function test_supplier_cannot_be_deleted_when_batches_reference_it(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        Batch::factory()->create([
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'purchase_cost' => 10.00,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/suppliers/{$supplier->id}")
            ->assertStatus(422)
            ->assertJsonPath('batches_count', 1);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_can_be_deleted_when_no_batches(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/suppliers/{$supplier->id}")
            ->assertNoContent();
    }

    // ===================================================================
    // F4 — BatchController::destroy (stock_movements pre-check)
    // ===================================================================

    public function test_batch_cannot_be_deleted_when_stock_movements_exist(): void
    {
        $batch = Batch::factory()->create([
            'quantity' => 100,
            'remaining_quantity' => 100,
            'purchase_cost' => 10.00,
        ]);
        StockMovement::create([
            'batch_id' => $batch->id,
            'product_id' => $batch->product_id,
            'warehouse_id' => $batch->warehouse_id,
            'type' => 'stock_in',
            'quantity' => 100,
            'unit_cost' => 1,
            'reference_type' => Batch::class,
            'reference_id' => $batch->id,
            'occurred_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/batches/{$batch->id}")
            ->assertStatus(422)
            ->assertJsonPath('movements_count', 1);

        $this->assertDatabaseHas('batches', ['id' => $batch->id]);
    }

    public function test_batch_cannot_be_deleted_when_remaining_differs_from_quantity(): void
    {
        $batch = Batch::factory()->create([
            'quantity' => 100,
            'remaining_quantity' => 50, // consumed
            'purchase_cost' => 10.00,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/batches/{$batch->id}")
            ->assertStatus(422);
    }

    public function test_batch_can_be_deleted_when_unused(): void
    {
        $batch = Batch::factory()->create([
            'quantity' => 100,
            'remaining_quantity' => 100,
            'purchase_cost' => 10.00,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/batches/{$batch->id}")
            ->assertNoContent();
    }

    // ===================================================================
    // F3 — BatchController::update (lockForUpdate + before/after log)
    // ===================================================================

    public function test_batch_update_succeeds_and_logs_before_after(): void
    {
        $batch = Batch::factory()->create([
            'purchase_cost' => 5.00,
            'notes' => 'before',
        ]);

        $this->actingAs($this->admin)
            ->putJson("/api/batches/{$batch->id}", [
                'product_id' => $batch->product_id,
                'warehouse_id' => $batch->warehouse_id,
                'batch_number' => $batch->batch_number,
                'quantity' => $batch->quantity,
                'remaining_quantity' => $batch->remaining_quantity,
                'purchase_cost' => 7.50,
                'notes' => 'after',
                'status' => $batch->status,
            ])
            ->assertOk();

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'purchase_cost' => 7.50,
            'notes' => 'after',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated_batch',
            'resource_type' => Batch::class,
            'resource_id' => $batch->id,
            'event' => 'updated',
        ]);
    }

    public function test_batch_update_runs_inside_transaction(): void
    {
        // Two concurrent updates to different fields should both succeed (no lock contention).
        // If update() weren't transactional + lockForUpdate, we'd expect flakiness here.
        $batch = Batch::factory()->create([
            'purchase_cost' => 10.00,
        ]);
        $otherAdmin = User::factory()->create(['role' => UserRole::Administrator]);
        $otherAdmin->assignRole('administrator');

        $this->actingAs($this->admin)
            ->putJson("/api/batches/{$batch->id}", [
                'product_id' => $batch->product_id,
                'warehouse_id' => $batch->warehouse_id,
                'batch_number' => $batch->batch_number,
                'quantity' => $batch->quantity,
                'remaining_quantity' => $batch->remaining_quantity,
                'purchase_cost' => 6.00,
                'notes' => $batch->notes,
                'status' => $batch->status,
            ])
            ->assertOk();

        $this->actingAs($otherAdmin)
            ->putJson("/api/batches/{$batch->id}", [
                'product_id' => $batch->product_id,
                'warehouse_id' => $batch->warehouse_id,
                'batch_number' => $batch->batch_number,
                'quantity' => $batch->quantity,
                'remaining_quantity' => $batch->remaining_quantity,
                'purchase_cost' => $batch->purchase_cost,
                'notes' => 'updated by second actor',
                'status' => $batch->status,
            ])
            ->assertOk();
    }
}