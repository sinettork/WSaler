<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'tax_number' => $this->tax_number,
            'payment_terms' => $this->payment_terms,
            'notes' => $this->notes,
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
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
