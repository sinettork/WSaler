<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['brand:id,name', 'category:id,name', 'baseUnit:id,name,short_code'])
            ->withCount(['variations', 'batches']);

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
        $product->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted_product',
            'description' => "Deleted product: {$product->name}",
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

    private function generateSku(): string
    {
        $next = (Product::withTrashed()->max('id') ?? 0) + 1;
        return 'PRD-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
