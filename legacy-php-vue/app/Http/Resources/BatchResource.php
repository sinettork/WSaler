<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
            ]),
            'variation' => $this->whenLoaded('variation', fn () => [
                'id' => $this->variation->id,
                'value' => $this->variation->value,
            ]),
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'code' => $this->warehouse->code,
            ]),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'quantity' => $this->quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->remaining_quantity - $this->reserved_quantity,
            'purchase_cost' => $this->purchase_cost,
            'manufacture_date' => $this->manufacture_date?->toIso8601String(),
            'expiry_date' => $this->expiry_date?->toIso8601String(),
            'received_date' => $this->received_date?->toIso8601String(),
            'notes' => $this->notes,
            'status' => $this->status,
            'is_expired' => $this->is_expired,
            'days_until_expiry' => $this->days_until_expiry,
            'expiry_status' => $this->expiry_status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
