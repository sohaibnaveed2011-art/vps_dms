<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;

class StoreCouponRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:coupons,code'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'organization_id' => ['required', 'exists:organizations,id'],
            
            // Scopes validation
            'scopes' => ['nullable', 'array'],
            'scopes.*.scopeable_type' => ['required_with:scopes', 'string'],
            'scopes.*.scopeable_id' => ['required_with:scopes', 'integer', 'min:1'],
            
            // Targets validation
            'targets' => ['nullable', 'array'],
            'targets.*.targetable_type' => ['required_with:targets', 'string'],
            'targets.*.targetable_id' => ['required_with:targets', 'integer', 'min:1'],
            
            // Customers validation
            'customers' => ['nullable', 'array'],
            'customers.*' => ['required_with:customers', 'integer', 'exists:customers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This coupon code already exists.',
            'valid_to.after' => 'Valid to date must be after valid from date.',
            'customers.*.exists' => 'One or more customer IDs do not exist.',
        ];
    }
}