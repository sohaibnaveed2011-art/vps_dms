<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'required|exists:organizations,id',
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'grand_total' => 'required|numeric|min:0',
        ];
    }
}
