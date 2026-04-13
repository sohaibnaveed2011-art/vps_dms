<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'required|exists:organizations,id',
            'branch_id' => 'nullable|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'voucher_type_id' => 'required|exists:voucher_types,id',
            'date' => 'required|date',
            'grand_total' => 'required|numeric|min:0',
            'status' => 'required|in:posted,paid,overdue,cancelled',
        ];
    }
}
