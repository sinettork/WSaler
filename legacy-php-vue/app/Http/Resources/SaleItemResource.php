<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale' => $this->whenLoaded('sale', fn () => [
                'id' => $this->sale->id,
                'invoice_number' => $this->sale->invoice_number,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
            ]),
            'variation' => $this->whenLoaded('variation', fn () => [
                'id' => $this->variation->id,
                'name' => $this->variation->name,
                'value' => $this->variation->value,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
                'short_name' => $this->unit->short_name,
            ]),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount' => $this->discount,
            'line_total' => $this->line_total,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
