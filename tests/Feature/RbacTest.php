<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::Administrator]);
        $superAdmin->assignRole('super_admin');

        $this->assertTrue($superAdmin->hasPermissionTo('manage permissions'));
        $this->assertTrue($superAdmin->hasPermissionTo('create products'));
        $this->assertTrue($superAdmin->hasPermissionTo('delete users'));
    }

    public function test_cashier_has_limited_permissions(): void
    {
        $cashier = $this->createUserWithRole(UserRole::Cashier->value);

        $this->assertTrue($cashier->hasPermissionTo('access pos'));
        $this->assertTrue($cashier->hasPermissionTo('apply discounts'));
        $this->assertFalse($cashier->hasPermissionTo('create products'));
        $this->assertFalse($cashier->hasPermissionTo('delete users'));
    }

    public function test_permission_middleware_allows_authorized(): void
    {
        $user = $this->createUserWithRole(UserRole::Administrator->value);

        $response = $this->actingAs($user)
            ->getJson('/api/products');

        $response->assertOk();
    }

    public function test_permission_middleware_denies_unauthorized(): void
    {
        $user = $this->createUserWithRole(UserRole::Cashier->value);

        $response = $this->actingAs($user)
            ->getJson('/api/users');

        $response->assertForbidden();
    }

    public function test_user_can_assign_roles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $admin->assignRole('super_admin');

        $user = User::factory()->create(['role' => UserRole::Cashier]);
        $user->assignRole('cashier');

        $this->assertTrue($user->hasRole('cashier'));
    }

    public function test_data_scoping_by_branch(): void
    {
        $warehouse1 = Warehouse::create(['name' => 'Branch A', 'code' => 'BA', 'is_active' => true]);
        $warehouse2 = Warehouse::create(['name' => 'Branch B', 'code' => 'BB', 'is_active' => true]);

        // Use warehouse_ids (which the batches table actually has) rather than branch_id,
        // because the batches table doesn't carry a branch_id column.
        $user = User::factory()->create([
            'role' => UserRole::WarehouseStaff,
            'warehouse_ids' => [$warehouse1->id],
        ]);
        $user->assignRole('warehouse_staff');

        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        $product1 = Product::factory()->create(['base_unit_id' => $unit->id, 'status' => 'active']);
        $product2 = Product::factory()->create(['base_unit_id' => $unit->id, 'status' => 'active']);

        Batch::create([
            'batch_number' => 'B-001',
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse1->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'purchase_cost' => 5,
            'status' => 'active',
            'received_date' => now()->toDateString(),
            'expiry_date' => now()->addMonth(),
        ]);

        Batch::create([
            'batch_number' => 'B-002',
            'product_id' => $product2->id,
            'warehouse_id' => $warehouse2->id,
            'quantity' => 20,
            'remaining_quantity' => 20,
            'purchase_cost' => 5,
            'status' => 'active',
            'received_date' => now()->toDateString(),
            'expiry_date' => now()->addMonth(),
        ]);

        // applyDataScoping lives on the DataScoping trait, used by controllers.
        $controller = app(\App\Http\Controllers\Api\BatchController::class);
        $query = $controller->applyDataScoping(Batch::query(), $user);

        $results = $query->get();
        $this->assertCount(1, $results);
        $this->assertEquals($warehouse1->id, $results->first()->warehouse_id);
    }

    public function test_approval_workflow(): void
    {
        $requester = User::factory()->create(['role' => UserRole::PurchasingStaff]);
        $requester->assignRole('purchasing_staff');

        $approver = User::factory()->create(['role' => UserRole::Manager]);
        $approver->assignRole('manager');

        $product = Product::factory()->create(['status' => 'draft']);

        $service = app(\App\Services\ApprovalService::class);
        $approval = $service->requestApproval(
            $product,
            $requester,
            'manager',
            ['amount' => 5000],
            'Need approval for new product'
        );

        $this->assertEquals('pending', $approval->status);
        $this->assertEquals($requester->id, $approval->requested_by);
        $this->assertEquals('manager', $approval->required_level);

        $approved = $service->approve($approval, $approver, 'Approved');

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals($approver->id, $approved->approver_id);
        $this->assertNotNull($approved->decided_at);
    }

    public function test_approval_rejection(): void
    {
        $requester = User::factory()->create(['role' => UserRole::PurchasingStaff]);
        $requester->assignRole('purchasing_staff');

        $approver = User::factory()->create(['role' => UserRole::Manager]);
        $approver->assignRole('manager');

        $product = Product::factory()->create(['status' => 'draft']);

        $service = app(\App\Services\ApprovalService::class);
        $approval = $service->requestApproval(
            $product,
            $requester,
            'manager'
        );

        $rejected = $service->reject($approval, $approver, 'Budget exceeded');

        $this->assertEquals('rejected', $rejected->status);
        $this->assertEquals($approver->id, $rejected->approver_id);
    }

    public function test_activity_log_extended_fields(): void
    {
        $user = User::factory()->create(['role' => UserRole::Administrator]);
        $user->assignRole('administrator');

        $log = \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'test_action',
            'description' => 'Test description',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'module' => 'products',
            'resource_type' => Product::class,
            'resource_id' => 1,
            'before' => ['name' => 'Old Name'],
            'after' => ['name' => 'New Name'],
            'event' => 'updated',
        ]);

        $this->assertEquals('products', $log->module);
        $this->assertEquals(Product::class, $log->resource_type);
        $this->assertEquals(1, $log->resource_id);
        $this->assertEquals(['name' => 'Old Name'], $log->before);
        $this->assertEquals(['name' => 'New Name'], $log->after);
        $this->assertEquals('updated', $log->event);
    }

    public function test_user_security_fields(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'login_attempts' => 3,
            'locked_until' => now()->addMinutes(15),
        ]);

        $this->assertTrue($user->two_factor_enabled);
        $this->assertEquals(3, $user->login_attempts);
        $this->assertNotNull($user->locked_until);
    }

    public function test_role_permissions_seeded(): void
    {
        $roles = ['super_admin', 'administrator', 'manager', 'sales_staff', 'cashier', 'purchasing_staff', 'warehouse_staff', 'delivery_staff', 'accountant'];

        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $this->assertNotNull($role, "Role {$roleName} should exist");
        }

        $this->assertNotNull(Permission::where('name', 'create products')->first());
        $this->assertNotNull(Permission::where('name', 'access pos')->first());
        $this->assertNotNull(Permission::where('name', 'manage permissions')->first());
    }
}
