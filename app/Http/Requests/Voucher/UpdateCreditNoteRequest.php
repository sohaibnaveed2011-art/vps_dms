<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'sometimes|required|exists:organizations,id',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'date' => 'sometimes|required|date',
            'grand_total' => 'sometimes|required|numeric|min:0',
        ];
    }
}
