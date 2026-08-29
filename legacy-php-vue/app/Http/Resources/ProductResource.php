<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'base_unit' => $this->whenLoaded('baseUnit', fn () => [
                'id' => $this->baseUnit->id,
                'name' => $this->baseUnit->name,
                'short_code' => $this->baseUnit->short_code,
            ]),
            'image_url' => $this->image_url,
            'retail_price' => $this->retail_price,
            'wholesale_price' => $this->wholesale_price,
            'distributor_price' => $this->distributor_price,
            'cost_price' => $this->cost_price,
            'status' => $this->status,
            'track_stock' => $this->track_stock,
            'total_stock' => $this->total_stock,
            'near_expiry_stock' => $this->near_expiry_stock,
            'variations' => ProductVariationResource::collection($this->whenLoaded('variations')),
            'batches' => $this->whenLoaded('batches', fn () => $this->batches->map(fn ($b) => [
                'id' => $b->id,
                'batch_number' => $b->batch_number,
                'expiry_date' => $b->expiry_date?->toIso8601String(),
                'remaining_quantity' => $b->remaining_quantity,
                'status' => $b->status,
            ])),
            'price_breaks' => $this->whenLoaded('priceBreaks'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
