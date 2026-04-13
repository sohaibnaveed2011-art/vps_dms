<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'required|exists:organizations,id',
            'purchase_bill_id' => 'required|exists:purchase_bills,id',
            'date' => 'required|date',
            'status' => 'required|in:received,inspected,rejected',
        ];
    }
}
