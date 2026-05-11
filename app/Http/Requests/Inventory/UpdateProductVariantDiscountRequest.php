<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariantDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 🔥 FIXED: Added application_type
            'application_type' => ['sometimes', 'in:sale,cost'],
            
            'discountable_type' => ['sometimes', 'string'],
            'discountable_id' => ['sometimes', 'integer'],
            
            // 🔥 FIXED: Changed 'type' to 'discount_type'
            'discount_type' => ['sometimes', 'in:percentage,fixed'],
            'value' => ['sometimes', 'numeric', 'min:0.01'],
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
            'application_type.in' => 'Application type must be either "sale" or "cost".',
            'discount_type.in' => 'Discount type must be either "percentage" or "fixed".',
            'value.min' => 'Discount value must be greater than zero.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority cannot exceed 100.',
            'max_discount_amount.min' => 'Max discount amount cannot be negative.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
        ];
    }
}