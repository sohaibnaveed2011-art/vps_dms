<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponScopeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'coupon_id' => 'required|exists:coupons,id',
            'scopeable_type' => 'required|string|max:150',
            'scopeable_id' => 'required|integer',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->scopeable_type) {
            $this->merge([
                'scopeable_type' => ltrim($this->scopeable_type, '\\'),
            ]);
        }
    }
}
