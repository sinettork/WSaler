<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150', Rule::unique('suppliers', 'email')->ignore($supplierId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'province_id' => ['sometimes', 'nullable', 'exists:provinces,id'],
            'district_id' => ['sometimes', 'nullable', 'exists:districts,id'],
            'commune_id' => ['sometimes', 'nullable', 'exists:communes,id'],
            'village_id' => ['sometimes', 'nullable', 'exists:villages,id'],
            'tax_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'payment_terms' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
