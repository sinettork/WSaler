<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Unit;
use App\Models\Batch;
use App\Traits\DataScoping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use DataScoping;

    public function index(Request $request)
    {
        $query = Product::with(['brand:id,name', 'category:id,name', 'baseUnit:id,name,short_code'])
            ->withCount(['variations', 'batches']);

        $query = $this->applyDataScoping($query);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->query('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $query->orderBy('name');

        return ProductResource::collection($query->paginate(20));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSku();
        }

        $product = DB::transaction(function () use ($data, $request) {
            $variations = $data['variations'] ?? [];
            unset($data['variations']);

            // Handle base_unit creation if provided
            $baseUnitData = $data['base_unit'] ?? null;
            unset($data['base_unit']);

            if ($baseUnitData) {
                $baseUnitData['base'] = true;
                $baseUnitData['conversion_factor_to_base'] = 1;
                $unit = Unit::create($baseUnitData);
                $data['base_unit_id'] = $unit->id;
            }

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('products', 'public');
            }

            $product = Product::create($data);

            foreach ($variations as $variation) {
                $product->variations()->create($variation);
            }

            return $product;
        });

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created_product',
            'description' => "Created product: {$product->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return (new ProductResource($product->load(['brand', 'category', 'baseUnit', 'variations'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product)
    {
        return new ProductResource(
            $product->load(['brand', 'category', 'baseUnit', 'variations', 'batches.warehouse', 'priceBreaks'])
        );
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $product, $request) {
            $variations = $data['variations'] ?? null;
            unset($data['variations']);

            if ($request->hasFile('image')) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $data['image'] = $request->file('image')->store('products', 'public');
            }

            $product->update($data);

            if (is_array($variations)) {
                $existingIds = $product->variations()->pluck('id')->toArray();
                $incomingIds = [];

                foreach ($variations as $variationData) {
                    if (isset($variationData['id'])) {
                        $variation = $product->variations()->find($variationData['id']);
                        if ($variation) {
                            $variation->update($variationData);
                            $incomingIds[] = $variation->id;
                        }
                    } else {
                        $new = $product->variations()->create($variationData);
                        $incomingIds[] = $new->id;
                    }
                }

                $toDelete = array_diff($existingIds, $incomingIds);
                if (!empty($toDelete)) {
                    $product->variations()->whereIn('id', $toDelete)->delete();
                }
            }
        });

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated_product',
            'description' => "Updated product: {$product->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new ProductResource(
            $product->fresh()->load(['brand', 'category', 'baseUnit', 'variations', 'batches.warehouse'])
        );
    }

    public function destroy(Request $request, Product $product)
    {
        // F5: block deletion when batches reference the product.
        $batchesCount = Batch::where('product_id', $product->id)->count();
        if ($batchesCount > 0) {
            return response()->json([
                'message' => 'Cannot delete product with associated batches. Reverse or transfer stock first.',
                'batches_count' => $batchesCount,
            ], 422);
        }

        // F5: block deletion when sale items reference the product (audit trail integrity).
        $saleItemsCount = \App\Models\SaleItem::where('product_id', $product->id)->count();
        if ($saleItemsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete product referenced by sale history.',
                'sale_items_count' => $saleItemsCount,
            ], 422);
        }

        try {
            $product->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Cannot delete product: rows in another table still reference it.',
                ], 422);
            }
            throw $e;
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted_product',
            'description' => "Deleted product: {$product->name}",
            'module' => 'products',
            'resource_type' => Product::class,
            'resource_id' => $product->id,
            'event' => 'deleted',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->noContent();
    }

    public function lookup(Request $request)
    {
        $search = $request->query('search', '');

        $products = Product::with('baseUnit:id,name,short_code')
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->limit(50)
            ->get(['id', 'name', 'sku', 'barcode', 'base_unit_id', 'retail_price', 'wholesale_price', 'distributor_price']);

        return response()->json(['data' => $products]);
    }

    /**
     * POS barcode scan — return exact-match product or variation.
     * Includes variations and FEFO batch stock per warehouse.
     */
    public function lookupBarcode(Request $request)
    {
        $code = $request->query('code', '');
        if (empty($code)) {
            return response()->json(['data' => null], 400);
        }

        // Try product direct match
        $product = Product::with(['baseUnit:id,name,short_code', 'variations:id,product_id,name,value,sku_suffix,barcode,additional_price,quantity_multiplier'])
            ->where('status', 'active')
            ->where('barcode', $code)
            ->first();

        // Try variation match if no product hit
        if (!$product) {
            $variation = ProductVariation::with([
                'product.baseUnit:id,name,short_code',
                'product:id,name,sku,barcode,base_unit_id,wholesale_price,retail_price,distributor_price,status,track_stock',
                'product.variations:id,product_id,name,value,sku_suffix,barcode,additional_price,quantity_multiplier',
            ])->where('barcode', $code)->first();

            if ($variation && $variation->product) {
                $variation = $variation->toArray();
                $product = $variation['product'];
                $product['matched_variation'] = $variation;
            }
        }

        if (!$product) {
            return response()->json(['data' => null], 404);
        }

        return response()->json(['data' => $product]);
    }

    /**
     * Return active batches for a product in a warehouse, sorted FEFO.
     * Used by POS when selecting batch-specific stock.
     */
    public function posBatches(Request $request, Product $product)
    {
        $warehouseId = (int) $request->query('warehouse_id', 0);

        $query = $product->batches()
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0)
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->orderBy('received_date', 'ASC')
            ->orderBy('id', 'ASC');

        if ($warehouseId > 0) {
            $query->where('warehouse_id', $warehouseId);
        }

        $batches = $query->with(['warehouse:id,name,code'])
            ->get(['id', 'batch_number', 'product_id', 'variation_id', 'warehouse_id', 'remaining_quantity', 'expiry_date', 'manufacture_date', 'received_date', 'purchase_cost']);

        return response()->json(['data' => $batches]);
    }

    private function generateSku(): string
    {
        $next = (Product::withTrashed()->max('id') ?? 0) + 1;
        return 'PRD-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
