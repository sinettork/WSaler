<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $piece = Unit::where('short_code', 'pcs')->first();
        $beverages = Category::where('name', 'Beverages')->first();
        $snacks = Category::where('name', 'Snacks')->first();
        $household = Category::where('name', 'Household')->first();
        $cocaCola = Brand::where('name', 'Coca-Cola')->first();
        $pepsiCo = Brand::where('name', 'PepsiCo')->first();
        $pg = Brand::where('name', 'P&G')->first();
        $nestle = Brand::where('name', 'Nestlé')->first();
        $supplier = Supplier::first();
        $mainWarehouse = Warehouse::where('code', 'WH-001')->first();
        $branchWarehouse = Warehouse::where('code', 'WH-002')->first();

        $products = [
            [
                'name' => 'Coca-Cola 330ml Can',
                'sku' => 'PRD-00001',
                'category' => $beverages,
                'brand' => $cocaCola,
                'retail_price' => 1.50,
                'wholesale_price' => 1.20,
                'distributor_price' => 1.00,
                'variations' => [
                    ['name' => 'Pack Size', 'value' => '6-pack', 'sku_suffix' => '-6PK'],
                    ['name' => 'Pack Size', 'value' => '12-pack', 'sku_suffix' => '-12PK'],
                ],
            ],
            [
                'name' => 'Coca-Cola 1.5L Bottle',
                'sku' => 'PRD-00002',
                'category' => $beverages,
                'brand' => $cocaCola,
                'retail_price' => 2.50,
                'wholesale_price' => 2.00,
                'distributor_price' => 1.75,
                'variations' => [
                    ['name' => 'Pack Size', 'value' => '6-pack', 'sku_suffix' => '-6PK'],
                    ['name' => 'Pack Size', 'value' => '12-pack', 'sku_suffix' => '-12PK'],
                ],
            ],
            [
                'name' => 'Lays Classic 50g',
                'sku' => 'PRD-00003',
                'category' => $snacks,
                'brand' => $pepsiCo,
                'retail_price' => 2.00,
                'wholesale_price' => 1.60,
                'distributor_price' => 1.30,
                'variations' => [
                    ['name' => 'Flavor', 'value' => 'Classic', 'sku_suffix' => '-CL'],
                    ['name' => 'Flavor', 'value' => 'BBQ', 'sku_suffix' => '-BBQ'],
                ],
            ],
            [
                'name' => 'Tide Powder 1kg',
                'sku' => 'PRD-00004',
                'category' => $household,
                'brand' => $pg,
                'retail_price' => 8.00,
                'wholesale_price' => 6.50,
                'distributor_price' => 5.50,
                'variations' => [
                    ['name' => 'Size', 'value' => '1kg', 'sku_suffix' => '-1KG'],
                    ['name' => 'Size', 'value' => '2kg', 'sku_suffix' => '-2KG'],
                ],
            ],
            [
                'name' => 'Dairy Milk Chocolate 50g',
                'sku' => 'PRD-00005',
                'category' => $snacks,
                'brand' => $nestle,
                'retail_price' => 3.00,
                'wholesale_price' => 2.40,
                'distributor_price' => 2.00,
                'variations' => [
                    ['name' => 'Flavor', 'value' => 'Milk', 'sku_suffix' => '-MK'],
                    ['name' => 'Flavor', 'value' => 'Dark', 'sku_suffix' => '-DK'],
                ],
            ],
        ];

        $globalBatchCounter = 1;
        foreach ($products as $index => $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'sku' => $productData['sku'],
                'barcode' => null,
                'description' => null,
                'brand_id' => $productData['brand']?->id,
                'category_id' => $productData['category']?->id,
                'base_unit_id' => $piece?->id,
                'image' => null,
                'retail_price' => $productData['retail_price'],
                'wholesale_price' => $productData['wholesale_price'],
                'distributor_price' => $productData['distributor_price'],
                'cost_price' => $productData['distributor_price'] * 0.8,
                'status' => 'active',
                'track_stock' => true,
            ]);

            foreach ($productData['variations'] as $variationData) {
                $variation = ProductVariation::create([
                    'product_id' => $product->id,
                    'name' => $variationData['name'],
                    'value' => $variationData['value'],
                    'sku_suffix' => $variationData['sku_suffix'],
                    'barcode' => null,
                    'additional_price' => 0,
                    'is_active' => true,
                ]);

                $warehouses = [$mainWarehouse, $branchWarehouse];
                foreach ($warehouses as $wh) {
                    if (! $wh) {
                        continue;
                    }
                    $qty = ($index + 1) * 50 + rand(10, 50);
                    Batch::create([
                        'batch_number' => 'BATCH-' . now()->format('Ymd') . '-' . str_pad($globalBatchCounter, 4, '0', STR_PAD_LEFT),
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'warehouse_id' => $wh->id,
                        'supplier_id' => $supplier?->id,
                        'quantity' => $qty,
                        'remaining_quantity' => $qty,
                        'reserved_quantity' => 0,
                        'purchase_cost' => $productData['distributor_price'] * 0.75,
                        'manufacture_date' => now()->subMonths(rand(1, 3)),
                        'expiry_date' => now()->addMonths(rand(6, 12)),
                        'received_date' => now()->subWeek(),
                        'notes' => null,
                        'status' => 'active',
                    ]);
                    $globalBatchCounter++;
                }
            }
        }
    }
}
