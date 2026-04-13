<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReceiptNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'sometimes|required|exists:organizations,id',
            'purchase_bill_id' => 'sometimes|required|exists:purchase_bills,id',
            'date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:received,inspected,rejected',
        ];
    }
}
