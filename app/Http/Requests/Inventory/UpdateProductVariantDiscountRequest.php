<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariantDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discountable_type' => ['nullable', 'string'],
            'discountable_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'in:percentage,fixed'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

