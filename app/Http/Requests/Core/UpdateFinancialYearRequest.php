<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Adjust permission logic if required
        return true;
    }

    public function rules(): array
    {
        // route('financial_year') or $this->route('financialYear') depending on your route key name
        $financialYearId = $this->route('financial_year') ?? $this->route('id') ?? $this->route('financialYear');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                // Unique per organization, excluding current record
                Rule::unique('financial_years')
                    ->where(function ($query) {
                        $organizationId = $this->input('organization_id') ?? $this->route('organization_id') ?? $this->route('organization');
                        if ($organizationId) {
                            $query->where('organization_id', $organizationId);
                        }
                    })
                    ->ignore($financialYearId),
            ],
            'start_date' => ['sometimes', 'required', 'date', 'before_or_equal:end_date'],
            'end_date'   => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'is_closed'  => ['sometimes', 'nullable', 'boolean'],
            'is_active'  => ['sometimes', 'nullable', 'boolean'],
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
