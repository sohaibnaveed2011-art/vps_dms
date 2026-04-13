<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'conversion_factor' => ['required', 'numeric', 'min:0.000001'],
            'is_base' => ['nullable', 'boolean'],
            'is_purchase_unit' => ['nullable', 'boolean'],
            'is_sale_unit' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

