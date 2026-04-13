<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Adjust authorization as needed (e.g., permission checks)
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per organization
                Rule::unique('financial_years')->where(fn ($query) => $query->where('organization_id', $this->input('organization_id'))),
            ],
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'is_closed'  => ['nullable', 'boolean'],
            'is_active'  => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'The name has already been taken for this organization.',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
        ];
    }
}
