<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('base')) {
            $this->merge(['conversion_factor_to_base' => 1]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('units', 'name')],
            'short_code' => ['required', 'string', 'max:10', Rule::unique('units', 'short_code')],
            'base' => ['boolean'],
            'conversion_factor_to_base' => ['required_if:base,false', 'numeric', 'min:0.0001'],
        ];
    }
}
