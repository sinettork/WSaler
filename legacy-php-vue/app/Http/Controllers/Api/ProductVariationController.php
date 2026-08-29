<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVariationRequest;
use App\Http\Requests\UpdateVariationRequest;
use App\Http\Resources\ProductVariationResource;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductVariationController extends Controller
{
    public function store(StoreVariationRequest $request, Product $product)
    {
        $variation = $product->variations()->create($request->validated());

        return (new ProductVariationResource($variation))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateVariationRequest $request, Product $product, ProductVariation $variation)
    {
        $variation->update($request->validated());

        return new ProductVariationResource($variation);
    }

    public function destroy(Request $request, Product $product, ProductVariation $variation)
    {
        // Prevent deletion if batches reference this variation
        if ($variation->batches()->exists()) {
            return response()->json([
                'message' => 'Cannot delete variation with associated batches.',
            ], 422);
        }

        $variation->delete();

        return response()->noContent();
    }
}
