<?php

namespace Tests\Feature\Services;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchPickerService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $saleService;
    private User $user;
    private Warehouse $warehouse;
    private Product $product;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saleService = app(SaleService::class);
        
        // Create test data
        $this->user = User::factory()->create(['role' => 'cashier']);
        $this->warehouse = Warehouse::factory()->create();
        $this->unit = Unit::factory()->create(['name' => 'Piece', 'abbreviation' => 'pc']);
        $this->product = Product::factory()->create([
            'base_unit_id' => $this->unit->id,
            'retail_price' => 100.00,
        ]);
    }

    /** @test */
    public function it_creates_a_sale_with_fefo_batch_allocation()
    {
        // Create 3 batches with different expiry dates
        $batch1 = Batch::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'remaining_quantity' => 50,
            'expiry_date' => now()->addDays(10), // Expires soonest
        ]);

        $batch2 = Batch::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'remaining_quantity' => 50,
            'expiry_date' => now()->addDays(30),
        ]);

        $batch3 = Batch::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'remaining_quantity' => 50,
            'expiry_date' => now()->addDays(60),
        ]);

        $data = [
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 80, // Should use batch1 (50) + batch2 (30)
                    'unit_price' => 100.00,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 8000.00],
            ],
        ];

        $sale = $this->saleService->createSale($this->user, $data);

        $this->assertNotNull($sale);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals(8000.00, $sale->total);
        
        // Verify FEFO: batch1 should be fully depleted, batch2 partially
        $batch1->refresh();
        $batch2->refresh();
        $batch3->refresh();
        
        $this->assertEquals(0, $batch1->remaining_quantity);
        $this->assertEquals(20, $batch2->remaining_quantity);
        $this->assertEquals(50, $batch3->remaining_quantity);
    }

    /** @test */
    public function it_throws_exception_when_insufficient_stock()
    {
        Batch::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'remaining_quantity' => 10,
        ]);

        $data = [
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 20, // More than available
                    'unit_price' => 100.00,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 2000.00],
            ],
        ];

        $this->expectException(\App\Exceptions\InsufficientStockException::class);
        $this->saleService->createSale($this->user, $data);
    }

    /** @test */
    public function it_enforces_customer_credit_limits()
    {
        $customer = Customer::factory()->create([
            'credit_limit' => 1000.00,
            'current_balance' => 500.00,
        ]);

        Batch::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'remaining_quantity' => 100,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 10,
                    'unit_price' => 100.00,
                ],
            ],
            'payments' => [
                ['method' => 'credit', 'amount' => 1000.00], // Would exceed limit
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Credit sale exceeds customer's credit limit");
        
        $this->saleService->createSale($this->user, $data);
    }

    /** @test */
    public function it_voids_a_sale_and_restores_stock()
    {
        $batch = Batch::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'remaining_quantity' => 100,
        ]);

        $data = [
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 20,
                    'unit_price' => 100.00,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 2000.00],
            ],
        ];

        $sale = $this->saleService->createSale($this->user, $data);
        
        // Verify stock was deducted
        $batch->refresh();
        $this->assertEquals(80, $batch->remaining_quantity);

        // Void the sale
        $voidedSale = $this->saleService->voidSale($sale, $this->user, 'Test void');

        $this->assertEquals('voided', $voidedSale->status);
        $this->assertNotNull($voidedSale->voided_at);
        
        // Verify stock was restored
        $batch->refresh();
        $this->assertEquals(100, $batch->remaining_quantity);
    }

    /** @test */
    public function it_handles_product_variations_with_multipliers()
    {
        $variation = ProductVariation::factory()->create([
            'product_id' => $this->product->id,
            'name' => '12-Pack',
            'quantity_multiplier' => 12, // 1 variation unit = 12 base units
        ]);

        $batch = Batch::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'remaining_quantity' => 100,
        ]);

        $data = [
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $variation->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 5, // 5 packs = 60 base units
                    'unit_price' => 1200.00,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 6000.00],
            ],
        ];

        $sale = $this->saleService->createSale($this->user, $data);

        // Verify 60 units (5 * 12) were deducted
        $batch->refresh();
        $this->assertEquals(40, $batch->remaining_quantity);
    }

    /** @test */
    public function it_creates_sale_with_multiple_payment_methods()
    {
        Batch::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'remaining_quantity' => 100,
        ]);

        $data = [
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 10,
                    'unit_price' => 100.00,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 500.00],
                ['method' => 'card', 'amount' => 500.00],
            ],
        ];

        $sale = $this->saleService->createSale($this->user, $data);

        $this->assertCount(2, $sale->payments);
        $this->assertEquals(1000.00, $sale->paid);
    }
}
