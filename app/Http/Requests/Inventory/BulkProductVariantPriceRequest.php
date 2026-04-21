<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;

class BulkProductVariantPriceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'discountable_type' => ['required', 'string'],
            'discountable_id' => ['required', 'integer'],

            // 🔥 MODE SWITCH (optional for uniform)
            'type' => ['nullable', 'in:percentage,fixed'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'stackable' => ['boolean'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],

            // 🔥 ITEMS
            'items' => ['required', 'array', 'min:1'],

            'items.*.product_variant_id' => [
                'required',
                'exists:product_variants,id'
            ],

            // Mixed mode fields (optional)
            'items.*.type' => ['nullable', 'in:percentage,fixed'],
            'items.*.value' => ['nullable', 'numeric', 'min:0'],
            'items.*.priority' => ['nullable', 'integer', 'min:1'],
            'items.*.stackable' => ['boolean'],
            'items.*.max_discount_amount' => ['nullable', 'numeric'],
            'items.*.start_date' => ['nullable', 'date'],
            'items.*.end_date' => ['nullable', 'date'],
            'items.*.is_active' => ['boolean'],
        ];
    }
}