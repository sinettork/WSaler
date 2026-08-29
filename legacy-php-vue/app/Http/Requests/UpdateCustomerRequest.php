<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customerId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'province_id' => ['sometimes', 'nullable', 'exists:provinces,id'],
            'district_id' => ['sometimes', 'nullable', 'exists:districts,id'],
            'commune_id' => ['sometimes', 'nullable', 'exists:communes,id'],
            'village_id' => ['sometimes', 'nullable', 'exists:villages,id'],
            'type' => ['sometimes', 'nullable', 'string', Rule::in(['retail', 'wholesale', 'distributor', 'vip'])],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'current_balance' => ['sometimes', 'numeric', 'min:0'],
            'payment_terms' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
