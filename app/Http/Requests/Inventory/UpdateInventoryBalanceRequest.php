<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_stock' => ['required', 'numeric', 'min:0'],
            'reorder_point' => ['required', 'numeric', 'min:0'],
        ];
    }
}

