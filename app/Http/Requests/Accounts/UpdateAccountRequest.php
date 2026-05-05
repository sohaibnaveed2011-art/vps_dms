<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Validation\Rule;
use App\Models\Accounts\Account;
use Illuminate\Validation\Validator;
use App\Http\Requests\BaseFormRequest;

class UpdateAccountRequest extends BaseFormRequest
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
        // Get account ID from route
        $accountId = $this->route('id');
        $organizationId = $this->organizationId();
        
        // Get the existing account as an object
        $existingAccount = $accountId ? Account::find($accountId) : null;
        
        // Start with base organization rules
        $rules = $this->organizationRules();

        // Add account-specific update rules
        $rules += [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
                'different:account',
                function ($attribute, $value, $fail) use ($accountId, $organizationId) {
                    if ($value == $accountId) {
                        $fail('Account cannot be its own parent.');
                        return;
                    }
                    
                    $parent = Account::find($value);
                    if ($parent && $organizationId && $parent->organization_id !== $organizationId) {
                        $fail('The parent account must belong to the same organization.');
                    }
                    
                    // Prevent circular references
                    $account = Account::find($accountId);
                    if ($account && $value) {
                        $descendants = $account->getAllDescendants()->pluck('id')->toArray();
                        if (in_array($value, $descendants)) {
                            $fail('Circular reference detected in account hierarchy.');
                        }
                    }
                },
            ],
            
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\-_\.]+$/',
                Rule::unique('accounts', 'code')
                    ->where('organization_id', $organizationId)
                    ->ignore($accountId, 'id'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['sometimes', 'integer', 'min:0', 'max:10'],
            
            'currency_code' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            
            'opening_balance' => [
                'nullable', 
                'numeric', 
                'between:-999999999.999999,999999999.999999',
                function ($attribute, $value, $fail) use ($accountId) {
                    $account = Account::find($accountId);
                    if ($account && $account->journalLines()->exists() && (float) $value !== (float) $account->opening_balance) {
                        $fail('Opening balance cannot be changed after transactions have been posted.');
                    }
                },
            ],
            
            'opening_balance_date' => [
                'nullable', 
                'date', 
                'before_or_equal:today',
                function ($attribute, $value, $fail) use ($accountId) {
                    $account = Account::find($accountId);
                    if ($account && $account->journalLines()->exists() && $value != $account->opening_balance_date) {
                        $fail('Opening balance date cannot be changed after transactions have been posted.');
                    }
                },
            ],
            
            'type' => [
                'sometimes',
                Rule::in(['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']),
                function ($attribute, $value, $fail) use ($accountId) {
                    $account = Account::find($accountId);
                    if ($account && $account->journalLines()->exists() && $value !== $account->type) {
                        $fail('Account type cannot be changed after transactions have been posted.');
                    }
                },
            ],
            
            'normal_balance' => [
                'nullable',
                Rule::in(['Debit', 'Credit']),
                function ($attribute, $value, $fail) use ($existingAccount) {
                    if ($value) {
                        $type = $this->input('type', $existingAccount->type ?? null);
                        $validCombinations = [
                            'Asset' => 'Debit',
                            'Expense' => 'Debit',
                            'Liability' => 'Credit',
                            'Equity' => 'Credit',
                            'Revenue' => 'Credit',
                        ];
                        
                        if ($type && isset($validCombinations[$type]) && $validCombinations[$type] !== $value) {
                            $fail("{$type} accounts typically have a normal balance of {$validCombinations[$type]}.");
                        }
                    }
                },
            ],
            
            'is_group' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_taxable' => ['sometimes', 'boolean'],
            'automatic_postings_disabled' => ['sometimes', 'boolean'],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Call parent to handle organization_id
        parent::prepareForValidation();
        
        $accountId = $this->route('id');
        $existingAccount = $accountId ? Account::find($accountId) : null;
        
        // Auto-update level based on parent change
        if ($this->has('parent_id') && $existingAccount && $this->parent_id !== $existingAccount->parent_id) {
            $parent = Account::find($this->parent_id);
            if ($parent) {
                $this->merge([
                    'level' => $parent->level + 1,
                ]);
            }
        }

        // Set default currency if not provided and account has no currency
        if (!$this->has('currency_code') && $existingAccount && !$existingAccount->currency_code) {
            $this->merge([
                'currency_code' => 'PKR',
            ]);
        }
        
        // Auto-set normal balance based on account type if not provided
        if (!$this->has('normal_balance') && $this->has('type') && $existingAccount && !$this->input('is_group', $existingAccount->is_group ?? false)) {
            $defaultNormalBalance = [
                'Asset' => 'Debit',
                'Expense' => 'Debit',
                'Liability' => 'Credit',
                'Equity' => 'Credit',
                'Revenue' => 'Credit',
            ];
            
            $type = $this->input('type');
            if ($type && isset($defaultNormalBalance[$type])) {
                $this->merge([
                    'normal_balance' => $defaultNormalBalance[$type],
                ]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $accountId = $this->route('id');
            $account = $accountId ? Account::find($accountId) : null;
            
            if (!$account) {
                return;
            }
            
            // Check if trying to deactivate an account with balance
            if ($this->has('is_active') && $this->is_active === false && $account->getCurrentBalanceAttribute() != 0) {
                $validator->errors()->add(
                    'is_active',
                    'Cannot deactivate account with non-zero balance.'
                );
            }
            
            // Check if trying to convert group account to non-group with children
            if ($this->has('is_group') && $this->is_group === false && $account->is_group === true && $account->children()->exists()) {
                $validator->errors()->add(
                    'is_group',
                    'Cannot convert group account to detailed account as it has child accounts.'
                );
            }
            
            // Check if trying to convert non-group account to group with transactions
            if ($this->has('is_group') && $this->is_group === true && $account->is_group === false && $account->journalLines()->exists()) {
                $validator->errors()->add(
                    'is_group',
                    'Cannot convert account with transactions to group account.'
                );
            }
            
            // Check if trying to change parent for account with transactions
            if ($this->has('parent_id') && $this->parent_id !== $account->parent_id && $account->journalLines()->exists()) {
                $validator->errors()->add(
                    'parent_id',
                    'Cannot change parent account after transactions have been posted.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'code.regex' => 'Account code can only contain letters, numbers, hyphens, underscores, and dots.',
            'code.unique' => 'This account code already exists in your organization.',
            'currency_code.size' => 'Currency code must be exactly 3 characters.',
            'currency_code.regex' => 'Currency code must be uppercase letters (e.g., USD, PKR, EUR).',
            'opening_balance.between' => 'Opening balance must be between -999,999,999.99 and 999,999,999.99.',
            'parent_id.different' => 'Account cannot be its own parent.',
            'parent_id.exists' => 'The selected parent account does not exist.',
            'normal_balance.in' => 'Normal balance must be either Debit or Credit.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'parent_id' => 'parent account',
            'name' => 'account name',
            'code' => 'account code',
            'type' => 'account type',
            'normal_balance' => 'normal balance',
            'is_group' => 'group account',
            'currency_code' => 'currency',
            'opening_balance' => 'opening balance',
            'opening_balance_date' => 'opening balance date',
            'is_taxable' => 'taxable status',
            'automatic_postings_disabled' => 'automatic postings disabled',
        ]);
    }
}