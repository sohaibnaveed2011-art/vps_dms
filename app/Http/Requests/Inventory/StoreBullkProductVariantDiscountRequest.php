<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;


class StoreBulkProductVariantDiscountRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'discountable_type' => ['required', 'string'],
            'discountable_id' => ['required', 'integer'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer'],
            'stackable' => ['nullable', 'boolean'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],

            // Single Discount applied on bulk items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
        ];
    }
}
