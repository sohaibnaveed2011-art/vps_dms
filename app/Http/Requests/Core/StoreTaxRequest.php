<?php

namespace App\Http\Requests\Core;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreTaxRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                // Rule to ensure name is unique only within the context of this organization
                Rule::unique('taxes')->where(fn ($q) => $q->where('organization_id', $this->input('organization_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                // Rule to ensure code is unique only within the context of this organization
                Rule::unique('taxes')->where(fn ($q) => $q->where('organization_id', $this->input('organization_id'))),
            ],
            'rate' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,4})?$/'], // Rate must be decimal:4 max
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        // Ensure rate is cast to float/decimal before validation checks
        if (isset($this->rate)) {
            $this->merge([
                'rate' => floatval($this->rate),
            ]);
        }
    }
}
