<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('suppliers', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'commune_id' => ['nullable', 'exists:communes,id'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
