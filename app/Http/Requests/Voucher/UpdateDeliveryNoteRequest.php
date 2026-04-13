<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'sometimes|required|exists:organizations,id',
            'invoice_id' => 'sometimes|required|exists:invoices,id',
            'date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:picked,in_transit,delivered',
        ];
    }
}
