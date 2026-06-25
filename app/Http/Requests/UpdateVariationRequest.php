<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variationId = $this->route('variation')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:50'],
            'value' => ['sometimes', 'string', 'max:100'],
            'sku_suffix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('product_variations', 'barcode')->ignore($variationId)],
            'additional_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'quantity_multiplier' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:99999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
