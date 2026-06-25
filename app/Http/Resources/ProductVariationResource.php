<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\resources\Json\JsonResource;

class ProductVariationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $multiplier = (int) ($this->quantity_multiplier ?? 1);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'value' => $this->value,
            'sku_suffix' => $this->sku_suffix,
            'barcode' => $this->barcode,
            'additional_price' => (float) $this->additional_price,
            'quantity_multiplier' => $multiplier,
            'pack_price_hint' => $this->when($this->relationLoaded('product') && $this->product, function () use ($multiplier) {
                return round(((float) ($this->product->wholesale_price ?? 0)) * $multiplier + (float) ($this->additional_price ?? 0), 2);
            }),
            'is_active' => (bool) $this->is_active,
            'full_label' => "{$this->name}: {$this->value}",
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
