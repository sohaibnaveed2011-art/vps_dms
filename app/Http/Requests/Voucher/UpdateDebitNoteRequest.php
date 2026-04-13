<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDebitNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'sometimes|required|exists:organizations,id',
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'date' => 'sometimes|required|date',
            'grand_total' => 'sometimes|required|numeric|min:0',
        ];
    }
}
