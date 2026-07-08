<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_barcode_lookup_finds_product_by_barcode(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        $product = Product::factory()->create([
            'barcode' => '1234567890123',
            'base_unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/products/lookup/barcode?code=1234567890123')
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_barcode_lookup_finds_variation_by_barcode(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        $product = Product::factory()->create([
            'base_unit_id' => $unit->id,
            'status' => 'active',
        ]);
        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Size',
            'value' => 'Large',
            'barcode' => 'VAR987654321',
        ]);

        $res = $this->actingAs($admin)
            ->getJson('/api/products/lookup/barcode?code=VAR987654321')
            ->assertOk();

        $data = $res->json('data');
        $this->assertEquals($product->id, $data['id']);
        $this->assertArrayHasKey('matched_variation', $data);
        $this->assertEquals($variation->id, $data['matched_variation']['id']);
    }

    public function test_barcode_lookup_returns_404_for_missing_barcode(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);

        $this->actingAs($admin)
            ->getJson('/api/products/lookup/barcode?code=NOEXIST999')
            ->assertNotFound();
    }

    public function test_pos_batches_returns_active_batches_fefo_order(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        $product = Product::factory()->create([
            'base_unit_id' => $unit->id,
            'status' => 'active',
        ]);
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $batch1 = Batch::create([
            'batch_number' => 'B-001',
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'purchase_cost' => 5,
            'expiry_date' => Carbon::tomorrow(),
            'received_date' => Carbon::today()->subDay(),
            'status' => 'active',
        ]);

        $batch2 = Batch::create([
            'batch_number' => 'B-002',
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 20,
            'remaining_quantity' => 20,
            'purchase_cost' => 5,
            'expiry_date' => Carbon::today()->addDays(30),
            'received_date' => Carbon::today()->subDays(2),
            'status' => 'active',
        ]);

        $res = $this->actingAs($admin)
            ->getJson("/api/products/{$product->id}/pos-batches?warehouse_id={$warehouse->id}")
            ->assertOk();

        $data = $res->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('B-001', $data[0]['batch_number']);
        $this->assertEquals('B-002', $data[1]['batch_number']);
    }

    public function test_pos_batches_excludes_expired_and_depleted_batches(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        $product = Product::factory()->create([
            'base_unit_id' => $unit->id,
            'status' => 'active',
        ]);
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        $active = Batch::create([
            'batch_number' => 'B-OK',
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'purchase_cost' => 5,
            'expiry_date' => Carbon::today()->addMonth(),
            'received_date' => Carbon::today(),
            'status' => 'active',
        ]);

        Batch::create([
            'batch_number' => 'B-EMPTY',
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'remaining_quantity' => 0,
            'purchase_cost' => 5,
            'expiry_date' => Carbon::today()->addMonth(),
            'received_date' => Carbon::today(),
            'status' => 'active',
        ]);

        $res = $this->actingAs($admin)
            ->getJson("/api/products/{$product->id}/pos-batches?warehouse_id={$warehouse->id}")
            ->assertOk();

        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('B-OK', $data[0]['batch_number']);
    }

    public function test_category_tree_returns_categories_with_product_counts(): void
    {
        $admin = $this->createUserWithRole(UserRole::Administrator->value);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);

        $parent = Category::create(['name' => 'Beverages', 'slug' => 'beverages', 'is_active' => true]);
        $child = Category::create(['name' => 'Soft Drinks', 'slug' => 'soft-drinks', 'parent_id' => $parent->id, 'is_active' => true]);

        Product::factory()->create(['category_id' => $parent->id, 'base_unit_id' => $unit->id, 'status' => 'active']);
        Product::factory()->create(['category_id' => $child->id, 'base_unit_id' => $unit->id, 'status' => 'active']);
        Product::factory()->create(['category_id' => $child->id, 'base_unit_id' => $unit->id, 'status' => 'active']);

        $res = $this->actingAs($admin)
            ->getJson('/api/categories/tree')
            ->assertOk();

        $data = $res->json('data');
        $this->assertNotEmpty($data);

        $parentData = collect($data)->firstWhere('id', $parent->id);
        $this->assertNotNull($parentData);
        $this->assertEquals(1, (int) $parentData['products_count']);

        $childData = collect($data)->firstWhere('id', $child->id);
        $this->assertNotNull($childData);
        $this->assertEquals(2, (int) $childData['products_count']);
    }

    public function test_user_can_create_draft_order(): void
    {
        $cashier = $this->createUserWithRole(UserRole::Cashier->value);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        $product = Product::factory()->create(['base_unit_id' => $unit->id, 'status' => 'active']);

        $payload = [
            'name' => 'POS 14:30',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 2,
                    'unit_price' => 10.00,
                ],
            ],
            'subtotal' => 20.00,
            'total' => 20.00,
        ];

        $this->actingAs($cashier)
            ->postJson('/api/draft-orders', $payload)
            ->assertCreated()
            ->assertJsonPath('data.items.0.product_id', $product->id);
    }

    public function test_user_can_list_and_delete_own_draft_orders(): void
    {
        $cashier = $this->createUserWithRole(UserRole::Cashier->value);

        $payload = [
            'name' => 'Draft A',
            'items' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 5.00,
                ],
            ],
            'subtotal' => 5.00,
            'total' => 5.00,
        ];

        $res = $this->actingAs($cashier)
            ->postJson('/api/draft-orders', $payload)
            ->assertCreated();

        $draftId = $res->json('data.id');

        $this->actingAs($cashier)
            ->getJson('/api/draft-orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($cashier)
            ->deleteJson("/api/draft-orders/{$draftId}")
            ->assertOk();

        $this->actingAs($cashier)
            ->getJson('/api/draft-orders')
            ->assertJsonCount(0, 'data');
    }

    public function test_user_cannot_access_another_users_draft(): void
    {
        $userA = $this->createUserWithRole(UserRole::Cashier->value);
        $userB = $this->createUserWithRole(UserRole::Cashier->value);

        $payload = [
            'name' => 'Private Draft',
            'items' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 5.00,
                ],
            ],
            'subtotal' => 5.00,
            'total' => 5.00,
        ];

        $res = $this->actingAs($userA)
            ->postJson('/api/draft-orders', $payload)
            ->assertCreated();

        $draftId = $res->json('data.id');

        $this->actingAs($userB)
            ->getJson("/api/draft-orders/{$draftId}")
            ->assertForbidden();
    }
}
