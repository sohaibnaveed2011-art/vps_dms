<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get the ID of the account being updated from the route (e.g., accounts/{account})
        $accountId = $this->route('account');
        // Get the organization ID from the request or the existing model (if available)
        $organizationId = $this->input('organization_id') ?? $this->account->organization_id ?? null;

        return [
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'string',
                'max:50',
                // CRITICAL: Unique constraint scoped by organization_id, ignoring the current account ID
                Rule::unique('accounts')->where(function ($query) use ($organizationId) {
                    return $query->where('organization_id', $organizationId);
                })->ignore($accountId),
            ],
            'type' => ['sometimes', Rule::in(['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
