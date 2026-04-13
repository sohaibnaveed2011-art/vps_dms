<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariantUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conversion_factor' => ['nullable', 'numeric', 'min:0.000001'],
            'is_base' => ['nullable', 'boolean'],
            'is_purchase_unit' => ['nullable', 'boolean'],
            'is_sale_unit' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

