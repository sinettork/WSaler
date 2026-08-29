<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'value' => ['required', 'string', 'max:100'],
            'sku_suffix' => ['nullable', 'string', 'max:20'],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('product_variations', 'barcode')],
            'additional_price' => ['nullable', 'numeric', 'min:0'],
            'quantity_multiplier' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_multiplier.min' => 'Quantity multiplier must be at least 1 (1\u00d7 base unit).',
            'quantity_multiplier.integer' => 'Quantity multiplier must be a whole number.',
        ];
    }
}
