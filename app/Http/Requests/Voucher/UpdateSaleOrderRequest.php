<?php

namespace App\Http\Requests\Voucher;

use App\Http\Requests\BaseFormRequest;
use App\Models\Partner\Customer;
use Illuminate\Validation\Rule;

class UpdateSaleOrderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $saleOrder = $this->route('sale_order');
        $saleOrderId = is_object($saleOrder) ? $saleOrder->id : (int) $saleOrder;

        return [
            /* -------------------------------------------------
             | SALE ORDER HEADER
             ------------------------------------------------- */
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'customer_id' => [
                'sometimes',
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
            'order_date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d'],
            'delivery_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:order_date'],
            
            /* -------------------------------------------------
             | SALE ORDER ITEMS
             ------------------------------------------------- */
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            
            'items.*.id' => ['nullable', 'integer', 'exists:document_items,id'],
            'items.*.product_variant_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) {
                    preg_match('/items\.(\d+)\.product_variant_id/', $attribute, $matches);
                    $itemIndex = $matches[1] ?? 'unknown';
                    
                    $variant = \App\Models\Inventory\ProductVariant::find($value);
                    if ($variant && !$variant->is_active) {
                        $fail("Item #{$itemIndex}: The selected product variant is inactive.");
                    }
                }
            ],
            
            'items.*.tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'items.*.quantity' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            
            /* -------------------------------------------------
             | WORKFLOW ACTIONS
             ------------------------------------------------- */
            'action' => ['nullable', 'string', Rule::in(['submit', 'review', 'approve', 'reject', 'confirm', 'cancel'])],
            'reason' => ['required_if:action,reject,cancel', 'nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
            
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
        // Recalculate line totals if items are provided
        if ($this->has('items')) {
            $items = $this->items;
            foreach ($items as $key => $item) {
                if (isset($item['quantity']) && isset($item['unit_price'])) {
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
            'items.min' => 'At least one item is required for the sale order.',
            'items.*.product_variant_id.required' => 'Product variant is required for each item.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
            'items.*.unit_price.min' => 'Unit price cannot be negative.',
            'reason.required_if' => 'Reason is required when rejecting or cancelling.',
            'delivery_date.after_or_equal' => 'Delivery date must be after or equal to order date.',
        ];
    }
}