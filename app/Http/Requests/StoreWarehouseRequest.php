<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('warehouses', 'code')],
            'address' => ['nullable', 'string', 'max:500'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'commune_id' => ['nullable', 'exists:communes,id'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
