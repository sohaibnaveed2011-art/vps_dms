<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Validation\Rule;
use App\Models\Partner\Customer;
use App\Models\Vouchers\VoucherType;
use App\Http\Requests\BaseFormRequest;

class StoreSaleOrderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /* -------------------------------------------------
             | SALE ORDER HEADER
             ------------------------------------------------- */
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
                function ($attribute, $value, $fail) {
                    $customer = Customer::find($value);
                    if ($customer && !$customer->is_active) {
                        $fail('The selected customer is inactive.');
                    }
                }
            ],
            'voucher_type_id' => [
                'required',
                'integer',
                'exists:voucher_types,id',
                function ($attribute, $value, $fail) {
                    $voucherType = VoucherType::find($value);
                    if ($voucherType && $voucherType->module !== 'sale') {
                        $fail('The selected voucher type is not valid for sales.');
                    }
                }
            ],
            'document_number' => ['nullable', 'string', 'max:255'],
            'order_date' => ['required', 'date', 'date_format:Y-m-d'],
            'delivery_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:order_date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'submitted'])],
            
            /* -------------------------------------------------
             | SALE ORDER ITEMS
             ------------------------------------------------- */
            'items' => ['required', 'array', 'min:1'],
            
            'items.*.product_variant_id' => [
                'required',
                'integer',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) {
                    // Extract item index for error context
                    preg_match('/items\.(\d+)\.product_variant_id/', $attribute, $matches);
                    $itemIndex = $matches[1] ?? 'unknown';
                    
                    // Check if product variant is active
                    $variant = \App\Models\Inventory\ProductVariant::find($value);
                    if ($variant && !$variant->is_active) {
                        $fail("Item #{$itemIndex}: The selected product variant is inactive.");
                    }
                    
                    // Check stock availability (optional)
                    $quantity = $this->input("items.{$itemIndex}.quantity", 0);
                    if ($variant && $variant->stock_quantity < $quantity) {
                        $fail("Item #{$itemIndex}: Insufficient stock. Available: {$variant->stock_quantity}");
                    }
                }
            ],
            
            'items.*.tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            
            /* -------------------------------------------------
             | ATTACHMENTS & COMMENTS
             ------------------------------------------------- */
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf,doc,docx', 'max:5120'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'draft']);
        }
        
        // Validate each item's line_total if not provided
        if ($this->has('items')) {
            $items = $this->items;
            foreach ($items as $key => $item) {
                if (!isset($item['line_total'])) {
                    $subtotal = $item['quantity'] * $item['unit_price'];
                    $discount = $item['discount_amount'] ?? 0;
                    $taxAmount = ($subtotal - $discount) * (($item['tax_rate'] ?? 0) / 100);
                    $items[$key]['line_total'] = round($subtotal - $discount + $taxAmount, 4);
                }
            }
            $this->merge(['items' => $items]);
        }
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required for the sale order.',
            'items.min' => 'At least one item is required for the sale order.',
            'items.*.product_variant_id.required' => 'Product variant is required for each item.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
            'items.*.unit_price.min' => 'Unit price cannot be negative.',
            'items.*.discount_amount.min' => 'Discount amount cannot be negative.',
            'delivery_date.after_or_equal' => 'Delivery date must be after or equal to order date.',
        ];
    }
}