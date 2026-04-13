<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseBillRequest extends FormRequest
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
            'date' => 'sometimes|required|date',
            'grand_total' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:posted,paid,overdue,cancelled',
        ];
    }
}
