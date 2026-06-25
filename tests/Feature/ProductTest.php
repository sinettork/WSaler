<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_list_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        Product::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/products')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_cashier_can_list_products(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);
        Product::factory()->count(3)->create();

        $this->actingAs($cashier)
            ->getJson('/api/products')
            ->assertOk();
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);

        $payload = [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'base_unit_id' => $unit->id,
            'retail_price' => 10.00,
            'wholesale_price' => 8.00,
            'distributor_price' => 6.50,
            'status' => 'active',
            'track_stock' => true,
        ];

        $this->actingAs($admin)
            ->postJson('/api/products', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Test Product');
    }

    public function test_admin_can_create_product_with_image(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);

        $payload = [
            'name' => 'Product with Image',
            'base_unit_id' => $unit->id,
            'retail_price' => 10.00,
            'wholesale_price' => 8.00,
            'distributor_price' => 6.00,
            'status' => 'active',
            'image' => UploadedFile::fake()->image('product.jpg'),
        ];

        $response = $this->actingAs($admin)->postJson('/api/products', $payload);
        $response->assertCreated();

        $product = Product::first();
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_cashier_cannot_create_product(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);

        $this->actingAs($cashier)
            ->postJson('/api/products', [
                'name' => 'Test',
                'base_unit_id' => $unit->id,
                'retail_price' => 10,
                'wholesale_price' => 8,
                'distributor_price' => 6,
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_sku_auto_generated_when_blank(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);

        $this->actingAs($admin)
            ->postJson('/api/products', [
                'name' => 'Auto SKU Product',
                'base_unit_id' => $unit->id,
                'retail_price' => 10,
                'wholesale_price' => 8,
                'distributor_price' => 6,
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.sku', 'PRD-00001');
    }

    public function test_admin_can_update_product_with_variations(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        $product = Product::factory()->create(['base_unit_id' => $unit->id]);
        $existing = $product->variations()->create(['name' => 'Size', 'value' => 'Small']);

        $payload = [
            'name' => 'Updated Name',
            'base_unit_id' => $unit->id,
            'retail_price' => 15,
            'wholesale_price' => 12,
            'distributor_price' => 10,
            'status' => 'active',
            'variations' => [
                ['id' => $existing->id, 'name' => 'Size', 'value' => 'Large'],
                ['name' => 'Color', 'value' => 'Red'],
            ],
        ];

        $this->actingAs($admin)
            ->putJson("/api/products/{$product->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertEquals(2, $product->fresh()->variations()->count());
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_lookup_endpoint_works(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);
        Product::factory()->create(['name' => 'Coca Cola']);
        Product::factory()->create(['name' => 'Pepsi', 'status' => 'inactive']);

        $response = $this->actingAs($cashier)
            ->getJson('/api/products/lookup?search=cola')
            ->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Coca Cola', $data[0]['name']);
    }
}
