<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;

class BulkProductVariantDiscountRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // =========================================================
            // SCOPE (applies to all items unless overridden)
            // =========================================================
            'discountable_type' => ['required', 'string'],
            'discountable_id' => ['required', 'integer'],

            // 🔥 FIXED: Added application_type (sale/cost) - required at parent level
            'application_type' => ['required', 'in:sale,cost'],

            // Default values for all items (optional)
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'value' => ['nullable', 'numeric', 'min:0.01'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100'],
            'stackable' => ['nullable', 'boolean'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],

            // =========================================================
            // ITEMS ARRAY
            // =========================================================
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => [
                'required',
                'integer',
                'exists:product_variants,id'
            ],

            // 🔥 FIXED: Changed 'type' to 'discount_type'
            'items.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'items.*.value' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.priority' => ['nullable', 'integer', 'min:1', 'max:100'],
            'items.*.stackable' => ['nullable', 'boolean'],
            'items.*.max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.start_date' => ['nullable', 'date'],
            'items.*.end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }
    
    /**
     * Prepare the data for validation (called after validation)
     */
    protected function prepareForValidation(): void
    {
        // If parent level values are provided, apply them to items that don't have overrides
        if ($this->has('items') && is_array($this->items)) {
            $items = $this->items;
            
            foreach ($items as $key => $item) {
                // Apply parent discount_type to item if not specified
                if (empty($item['discount_type']) && $this->has('discount_type')) {
                    $items[$key]['discount_type'] = $this->discount_type;
                }
                
                // Apply parent value to item if not specified
                if (empty($item['value']) && $this->has('value')) {
                    $items[$key]['value'] = $this->value;
                }
                
                // Apply parent priority to item if not specified
                if (empty($item['priority']) && $this->has('priority')) {
                    $items[$key]['priority'] = $this->priority;
                }
                
                // Apply parent stackable to item if not specified
                if (!isset($item['stackable']) && $this->has('stackable')) {
                    $items[$key]['stackable'] = $this->stackable;
                }
                
                // Apply parent max_discount_amount to item if not specified
                if (empty($item['max_discount_amount']) && $this->has('max_discount_amount')) {
                    $items[$key]['max_discount_amount'] = $this->max_discount_amount;
                }
                
                // Apply parent start_date to item if not specified
                if (empty($item['start_date']) && $this->has('start_date')) {
                    $items[$key]['start_date'] = $this->start_date;
                }
                
                // Apply parent end_date to item if not specified
                if (empty($item['end_date']) && $this->has('end_date')) {
                    $items[$key]['end_date'] = $this->end_date;
                }
                
                // Apply parent is_active to item if not specified
                if (!isset($item['is_active']) && $this->has('is_active')) {
                    $items[$key]['is_active'] = $this->is_active;
                }
            }
            
            $this->merge(['items' => $items]);
        }
    }
    
    public function messages(): array
    {
        return [
            'application_type.required' => 'Please specify if this is a sale or cost discount.',
            'application_type.in' => 'Application type must be either "sale" or "cost".',
            'discountable_type.required' => 'Discountable type is required.',
            'discountable_id.required' => 'Discountable ID is required.',
            'items.required' => 'At least one discount item is required.',
            'items.min' => 'At least one discount item is required.',
            'items.*.product_variant_id.required' => 'Product variant ID is required for each item.',
            'items.*.product_variant_id.exists' => 'One or more selected product variants do not exist.',
            'items.*.value.min' => 'Discount value must be greater than zero.',
            'items.*.priority.min' => 'Priority must be at least 1.',
            'items.*.priority.max' => 'Priority cannot exceed 100.',
        ];
    }
}