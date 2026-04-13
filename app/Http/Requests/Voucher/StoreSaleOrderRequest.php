<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via controller / policy
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'branch_id'       => ['nullable', 'exists:branches,id'],
            'warehouse_id'    => ['required', 'exists:warehouses,id'],
            'outlet_id'       => ['nullable', 'exists:outlets,id'],
            'customer_id'     => ['required', 'exists:customers,id'],
            'voucher_type_id' => ['required', 'exists:voucher_types,id'],

            'document_number' => ['required', 'string', 'max:50'],
            'order_date'      => ['required', 'date'],
            'delivery_date'   => ['nullable', 'date', 'after_or_equal:order_date'],

            'grand_total'     => ['required', 'numeric', 'min:0'],

            'items'           => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.tax_id'  => ['nullable', 'exists:taxes,id'],
            'items.*.batch_id'=> ['nullable', 'exists:batches,id'],

            'items.*.quantity'        => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price'      => ['required', 'numeric', 'gte:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_rate'         => ['nullable', 'numeric', 'gte:0'],
            'items.*.line_total'       => ['required', 'numeric', 'gte:0'],
            'items.*.notes'            => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required for a sale order.',
            'items.min'      => 'Sale order must contain at least one item.',
        ];
    }
}
