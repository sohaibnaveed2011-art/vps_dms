<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'sometimes|required|exists:organizations,id',
            'branch_id' => 'nullable|exists:branches,id',
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'voucher_type_id' => 'sometimes|required|exists:voucher_types,id',
            'order_date' => 'sometimes|required|date',
            'expected_receipt_date' => 'sometimes|required|date|after_or_equal:order_date',
            'grand_total' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:draft,ordered,received,cancelled',
        ];
    }
}
