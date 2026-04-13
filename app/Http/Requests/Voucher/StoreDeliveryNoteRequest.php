<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'required|exists:organizations,id',
            'invoice_id' => 'required|exists:invoices,id',
            'date' => 'required|date',
            'status' => 'required|in:picked,in_transit,delivered',
        ];
    }
}
