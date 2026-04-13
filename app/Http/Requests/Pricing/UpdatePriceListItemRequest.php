<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePriceListItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'price' => 'sometimes|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:1',
        ];
    }
}
