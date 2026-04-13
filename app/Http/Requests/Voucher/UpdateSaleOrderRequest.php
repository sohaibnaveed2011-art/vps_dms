<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'outlet_id'    => ['nullable', 'exists:outlets,id'],
            'customer_id'  => ['required', 'exists:customers,id'],

            'order_date'    => ['required', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],

            'grand_total' => ['required', 'numeric', 'min:0'],

            'items'           => ['sometimes', 'array', 'min:1'],
            'items.*.item_id' => ['required_with:items', 'exists:items,id'],
            'items.*.tax_id'  => ['nullable', 'exists:taxes,id'],
            'items.*.batch_id'=> ['nullable', 'exists:batches,id'],

            'items.*.quantity'        => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit_price'      => ['required_with:items', 'numeric', 'gte:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_rate'         => ['nullable', 'numeric', 'gte:0'],
            'items.*.line_total'       => ['required_with:items', 'numeric', 'gte:0'],
            'items.*.notes'            => ['nullable', 'string'],
        ];
    }
}
