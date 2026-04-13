<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
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
            'order_date' => 'required|date',
            'expected_receipt_date' => 'required|date|after_or_equal:order_date',
            'grand_total' => 'required|numeric|min:0',
            'status' => 'required|in:draft,ordered,received,cancelled',
        ];
    }
}
