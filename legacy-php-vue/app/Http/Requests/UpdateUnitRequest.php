<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('base') && $this->boolean('base')) {
            $this->merge(['conversion_factor_to_base' => 1]);
        }
    }

    public function rules(): array
    {
        $unitId = $this->route('unit')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:50', Rule::unique('units', 'name')->ignore($unitId)],
            'short_code' => ['sometimes', 'string', 'max:10', Rule::unique('units', 'short_code')->ignore($unitId)],
            'base' => ['sometimes', 'boolean'],
            'conversion_factor_to_base' => ['sometimes', 'numeric', 'min:0.0001'],
        ];
    }
}
