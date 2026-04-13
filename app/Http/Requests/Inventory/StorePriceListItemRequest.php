<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StorePriceListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_list_id' => ['required', 'integer', 'exists:price_lists,id'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}

