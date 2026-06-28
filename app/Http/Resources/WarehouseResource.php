<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'phone' => $this->phone,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'province_id' => $this->province_id,
            'district_id' => $this->district_id,
            'commune_id' => $this->commune_id,
            'village_id' => $this->village_id,
            'province' => $this->whenLoaded('province', fn () => [
                'id' => $this->province->id,
                'code' => $this->province->code,
                'name' => $this->province->name,
                'name_km' => $this->province->name_km,
            ]),
            'district' => $this->whenLoaded('district', fn () => [
                'id' => $this->district->id,
                'code' => $this->district->code,
                'name' => $this->district->name,
                'name_km' => $this->district->name_km,
            ]),
            'commune' => $this->whenLoaded('commune', fn () => [
                'id' => $this->commune->id,
                'code' => $this->commune->code,
                'name' => $this->commune->name,
                'name_km' => $this->commune->name_km,
            ]),
            'village' => $this->whenLoaded('village', fn () => [
                'id' => $this->village->id,
                'code' => $this->village->code,
                'name' => $this->village->name,
                'name_km' => $this->village->name_km,
            ]),
            'batches_count' => $this->whenCounted('batches'),
            'total_stock_value' => $this->when(isset($this->total_stock_value), $this->total_stock_value),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
