<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariantPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['sometimes','required', 'integer', 'exists:product_variants,id'],
            'priceable_type' => ['sometimes','required', 'string'],
            'priceable_id' => ['sometimes','required', 'integer'],
            'cost_price' => ['nullable', 'numeric'],
            'sale_price' => ['nullable', 'numeric'],
            'is_override' => ['boolean'],
        ];
    }
}

