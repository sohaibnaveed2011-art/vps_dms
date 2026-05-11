<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;

class StoreProductVariantDiscountRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required fields
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'discountable_type' => ['required', 'string'],
            'discountable_id' => ['required', 'integer'],
            
            // 🔥 FIXED: Added application_type (sale/cost)
            'application_type' => ['required', 'in:sale,cost'],
            
            // 🔥 FIXED: Changed 'type' to 'discount_type'
            'discount_type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            
            // Optional fields
            'priority' => ['nullable', 'integer', 'min:1', 'max:100'],
            'stackable' => ['nullable', 'boolean'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'application_type.required' => 'Please specify if this is a sale or cost discount.',
            'application_type.in' => 'Application type must be either "sale" or "cost".',
            'discount_type.required' => 'Discount type is required.',
            'discount_type.in' => 'Discount type must be either "percentage" or "fixed".',
            'value.required' => 'Discount value is required.',
            'value.min' => 'Discount value must be greater than zero.',
            'product_variant_id.exists' => 'The selected product variant does not exist.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
        ];
    }
}