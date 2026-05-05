<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class ListGlTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Scoping
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],

            // Date Range
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            // Search
            'document_number' => ['nullable', 'string', 'max:255'],

            // Pagination
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
