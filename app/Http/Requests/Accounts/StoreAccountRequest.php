<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Validation\Rule;
use App\Models\Accounts\Account;
use App\Http\Requests\BaseFormRequest;

class StoreAccountRequest extends BaseFormRequest
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
     */
    public function rules(): array
    {
        $organizationId = $this->organizationId();

        // Start with organization rules from base class
        $rules = $this->organizationRules();

        // Add account-specific rules
        $rules += [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
                function ($attribute, $value, $fail) use ($organizationId) {
                    $parent = Account::find($value);
                    if ($parent && $parent->organization_id !== $organizationId) {
                        $fail('The parent account must belong to the same organization.');
                    }
                    if ($parent && $parent->parent_id === $this->route('account')) {
                        $fail('Circular reference detected in account hierarchy.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\-_\.]+$/',
                Rule::unique('accounts')->where(function ($query) use ($organizationId) {
                    return $query->where('organization_id', $organizationId);
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['nullable', 'integer', 'min:0', 'max:10'],
            'currency_code' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'opening_balance' => ['nullable', 'numeric', 'between:-999999999.999999,999999999.999999'],
            'opening_balance_date' => ['nullable', 'date', 'before_or_equal:today'],
            'type' => [
                'required',
                Rule::in(['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']),
            ],
            'normal_balance' => [
                'nullable',
                Rule::in(['Debit', 'Credit']),
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $validCombinations = [
                            'Asset' => 'Debit',
                            'Expense' => 'Debit',
                            'Liability' => 'Credit',
                            'Equity' => 'Credit',
                            'Revenue' => 'Credit',
                        ];
                        
                        $type = $this->input('type');
                        if (isset($validCombinations[$type]) && $validCombinations[$type] !== $value) {
                            $fail("{$type} accounts typically have a normal balance of {$validCombinations[$type]}.");
                        }
                    }
                },
            ],
            'is_group' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_taxable' => ['nullable', 'boolean'],
            'automatic_postings_disabled' => ['nullable', 'boolean'],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Call parent to set organization_id
        parent::prepareForValidation();

        // Auto-calculate level based on parent
        if ($this->has('parent_id') && !$this->has('level')) {
            $parent = Account::find($this->parent_id);
            if ($parent) {
                $this->merge([
                    'level' => $parent->level + 1,
                ]);
            }
        }

        // Set default currency if not provided
        if (!$this->has('currency_code')) {
            $this->merge([
                'currency_code' => 'PKR',
            ]);
        }

        // Set default values for flags
        $this->merge([
            'is_group' => $this->input('is_group', false),
            'is_active' => $this->input('is_active', true),
            'is_taxable' => $this->input('is_taxable', false),
            'automatic_postings_disabled' => $this->input('automatic_postings_disabled', false),
        ]);
        
        // Auto-set normal balance based on account type if not provided
        if (!$this->has('normal_balance') && $this->has('type') && !$this->input('is_group', false)) {
            $defaultNormalBalance = [
                'Asset' => 'Debit',
                'Expense' => 'Debit',
                'Liability' => 'Credit',
                'Equity' => 'Credit',
                'Revenue' => 'Credit',
            ];
            
            $type = $this->input('type');
            if (isset($defaultNormalBalance[$type])) {
                $this->merge([
                    'normal_balance' => $defaultNormalBalance[$type],
                ]);
            }
        }
    }
}