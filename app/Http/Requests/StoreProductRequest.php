<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')],
            'description' => ['nullable', 'string'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'distributor_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'track_stock' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'variations' => ['nullable', 'array'],
            'variations.*.name' => ['required_with:variations', 'string', 'max:50'],
            'variations.*.value' => ['required_with:variations', 'string', 'max:100'],
            'variations.*.sku_suffix' => ['nullable', 'string', 'max:20'],
            'variations.*.barcode' => ['nullable', 'string', 'max:50'],
            'variations.*.additional_price' => ['nullable', 'numeric', 'min:0'],
            'variations.*.is_active' => ['boolean'],
        ];
    }
}
