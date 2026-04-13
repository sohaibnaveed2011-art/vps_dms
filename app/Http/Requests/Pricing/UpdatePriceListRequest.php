<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePriceListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'currency' => 'sometimes|string|max:10',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'priority' => 'sometimes|integer|min:1',
        ];
    }
}
