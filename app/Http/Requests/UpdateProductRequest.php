<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'sku' => ['sometimes', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($productId)],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->ignore($productId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'base_unit_id' => ['sometimes', 'integer', 'exists:units,id'],
            'retail_price' => ['sometimes', 'numeric', 'min:0'],
            'wholesale_price' => ['sometimes', 'numeric', 'min:0'],
            'distributor_price' => ['sometimes', 'numeric', 'min:0'],
            'cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:active,inactive'],
            'track_stock' => ['sometimes', 'boolean'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'variations' => ['sometimes', 'nullable', 'array'],
            'variations.*.id' => ['sometimes', 'integer'],
            'variations.*.name' => ['required_with:variations', 'string', 'max:50'],
            'variations.*.value' => ['required_with:variations', 'string', 'max:100'],
            'variations.*.sku_suffix' => ['nullable', 'string', 'max:20'],
            'variations.*.barcode' => ['nullable', 'string', 'max:50'],
            'variations.*.additional_price' => ['nullable', 'numeric', 'min:0'],
            'variations.*.is_active' => ['boolean'],
        ];
    }
}
