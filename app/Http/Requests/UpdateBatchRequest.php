<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['sometimes', 'exists:products,id'],
            'warehouse_id' => ['sometimes', 'exists:warehouses,id'],
            'supplier_id' => ['sometimes', 'nullable', 'exists:suppliers,id'],
            'batch_number' => ['sometimes', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'remaining_quantity' => ['sometimes', 'integer', 'min:0'],
            'purchase_cost' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'expiry_date' => ['sometimes', 'nullable', 'date'],
            'manufacture_date' => ['sometimes', 'nullable', 'date'],
            'received_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:active,expired,depleted,disposed'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
