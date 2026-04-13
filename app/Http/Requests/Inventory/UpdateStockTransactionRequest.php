<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.000001'],
            'unit_cost' => ['sometimes', 'required', 'numeric', 'min:0'],
            'remarks' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
