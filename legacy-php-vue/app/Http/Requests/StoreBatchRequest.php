<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_number' => ['nullable', 'string', 'max:50', Rule::unique('batches', 'batch_number')],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variation_id' => ['nullable', 'integer', 'exists:product_variations,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'remaining_quantity' => ['nullable', 'integer', 'min:0'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'received_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,depleted,expired,quarantined'],
        ];
    }
}
