<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;

class BulkProductVariantPriceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'priceable_type' => ['required', 'string'],
            'priceable_id' => ['required', 'integer'],
            'is_override' => ['boolean'],

            // 🔥 ITEMS
            'items' => ['required', 'array', 'min:1'],

            'items.*.product_variant_id' => [
                'required',
                'exists:product_variants,id'
            ],
            'items.*.cost_price' => ['nullable', 'numeric'],
            'items.*.sale_price' => ['nullable', 'numeric'],
        ];
    }
}