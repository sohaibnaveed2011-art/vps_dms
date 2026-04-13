<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponTargetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'coupon_id' => 'required|exists:coupons,id',
            'targetable_type' => 'required|string|max:150',
            'targetable_id' => 'required|integer',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->targetable_type) {
            $this->merge([
                'targetable_type' => ltrim($this->targetable_type, '\\'),
            ]);
        }
    }
}
