<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class StorePriceListItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_variant_id' => 'required|exists:product_variants,id',
            'price' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:1',
        ];
    }
}
