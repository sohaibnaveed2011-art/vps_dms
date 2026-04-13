<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // In a real application, check if the authenticated user has
        // permission to update taxes and owns the tax record identified by $this->route('tax')
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $organizationId = $this->input('organization_id');
        $taxId = $this->route('tax'); // Assuming route parameter is named 'tax'

        return [
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'name' => [
                'sometimes',
                'string',
                'max:255',
                // Ignore the current tax ID from the unique check
                Rule::unique('taxes')->where(function ($query) use ($organizationId) {
                    return $query->where('organization_id', $organizationId);
                })->ignore($taxId),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                // Ignore the current tax ID from the unique check
                Rule::unique('taxes')->where(function ($query) use ($organizationId) {
                    return $query->where('organization_id', $organizationId);
                })->ignore($taxId),
            ],
            'rate' => ['sometimes', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,4})?$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure rate is cast to float/decimal before validation checks
        if (isset($this->rate)) {
            $this->merge([
                'rate' => floatval($this->rate),
            ]);
        }
    }
}
