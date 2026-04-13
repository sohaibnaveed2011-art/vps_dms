<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->input('organization_id');

        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                // CRITICAL: Unique constraint scoped by organization_id
                Rule::unique('accounts')->where(function ($query) use ($organizationId) {
                    return $query->where('organization_id', $organizationId);
                }),
            ],
            'type' => ['required', Rule::in(['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
