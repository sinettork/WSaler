<?php

namespace Tests\Unit\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\FefoBatchSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FefoBatchSelectorTest extends TestCase
{
    use RefreshDatabase;

    protected FefoBatchSelector $selector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selector = new FefoBatchSelector();
    }

    protected function createProduct(): Product
    {
        $unit = Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-001', 'is_default' => true, 'is_active' => true]);

        return Product::create([
            'name' => 'Test Product',
            'sku' => 'TST-001',
            'base_unit_id' => $unit->id,
            'retail_price' => 1,
            'wholesale_price' => 1,
            'distributor_price' => 1,
            'status' => 'active',
        ]);
    }

    protected function createBatch(Product $product, int $quantity, ?string $expiryDate, string $batchNumber): Batch
    {
        $warehouse = Warehouse::first();

        return Batch::create([
            'batch_number' => $batchNumber,
            'product_id' => $product->id,
            'variation_id' => null,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => null,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'reserved_quantity' => 0,
            'purchase_cost' => 1,
            'manufacture_date' => null,
            'expiry_date' => $expiryDate,
            'received_date' => now(),
            'notes' => null,
            'status' => 'active',
        ]);
    }

    public function test_selects_batches_fefo_order(): void
    {
        $product = $this->createProduct();
        $batchA = $this->createBatch($product, 100, '2027-01-01', 'B-A');
        $batchB = $this->createBatch($product, 200, '2027-06-01', 'B-B');

        $allocations = $this->selector->selectForProduct($product->id, 150);

        $this->assertCount(2, $allocations);
        $this->assertEquals($batchA->id, $allocations[0]['batch']->id);
        $this->assertEquals(100, $allocations[0]['quantity']);
        $this->assertEquals($batchB->id, $allocations[1]['batch']->id);
        $this->assertEquals(50, $allocations[1]['quantity']);
    }

    public function test_selects_exact_quantity_from_multiple_batches(): void
    {
        $product = $this->createProduct();
        $batchA = $this->createBatch($product, 100, '2027-01-01', 'B-A');
        $batchB = $this->createBatch($product, 200, '2027-06-01', 'B-B');

        $allocations = $this->selector->selectForProduct($product->id, 250);

        $this->assertCount(2, $allocations);
        $this->assertEquals(100, $allocations[0]['quantity']);
        $this->assertEquals(150, $allocations[1]['quantity']);
    }

    public function test_selects_single_batch_when_sufficient(): void
    {
        $product = $this->createProduct();
        $batchA = $this->createBatch($product, 100, '2027-01-01', 'B-A');
        $this->createBatch($product, 200, '2027-06-01', 'B-B');

        $allocations = $this->selector->selectForProduct($product->id, 50);

        $this->assertCount(1, $allocations);
        $this->assertEquals($batchA->id, $allocations[0]['batch']->id);
        $this->assertEquals(50, $allocations[0]['quantity']);
    }

    public function test_throws_when_not_enough_total(): void
    {
        $this->expectException(InsufficientStockException::class);

        $product = $this->createProduct();
        $this->createBatch($product, 100, '2027-01-01', 'B-A');
        $this->createBatch($product, 200, '2027-06-01', 'B-B');

        $this->selector->selectForProduct($product->id, 350);
    }

    public function test_excludes_expired_batches(): void
    {
        $product = $this->createProduct();
        $batchA = $this->createBatch($product, 100, '2027-01-01', 'B-A');
        $batchB = $this->createBatch($product, 200, '2027-06-01', 'B-B');
        $this->createBatch($product, 50, '2025-01-01', 'B-C'); // expired

        $allocations = $this->selector->selectForProduct($product->id, 250);

        $this->assertCount(2, $allocations);
        $this->assertEquals(100, $allocations[0]['quantity']);
        $this->assertEquals(150, $allocations[1]['quantity']);
    }

    public function test_earliest_non_expired_first(): void
    {
        $product = $this->createProduct();
        $this->createBatch($product, 100, '2027-01-01', 'B-A');
        $this->createBatch($product, 200, '2027-06-01', 'B-B');
        $this->createBatch($product, 50, '2025-01-01', 'B-C'); // expired
        $batchD = $this->createBatch($product, 1000, now()->addDays(10)->format('Y-m-d'), 'B-D');

        $allocations = $this->selector->selectForProduct($product->id, 50);

        $this->assertCount(1, $allocations);
        $this->assertEquals($batchD->id, $allocations[0]['batch']->id);
        $this->assertEquals(50, $allocations[0]['quantity']);
    }

    public function test_throws_when_insufficient_stock(): void
    {
        $this->expectException(InsufficientStockException::class);

        $product = $this->createProduct();
        $this->createBatch($product, 100, '2027-01-01', 'B-A');
        $this->createBatch($product, 200, '2027-06-01', 'B-B');
        $this->createBatch($product, 50, '2025-01-01', 'B-C'); // expired
        $this->createBatch($product, 1000, now()->addDays(10)->format('Y-m-d'), 'B-D');

        $this->selector->selectForProduct($product->id, 5000);
    }

    public function test_returns_empty_when_no_batches(): void
    {
        $product = $this->createProduct();
        $allocations = $this->selector->selectForProduct($product->id, 10);
        $this->assertEmpty($allocations);
    }
}
