<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionScopeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'promotion_id' => 'required|exists:promotions,id',
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
