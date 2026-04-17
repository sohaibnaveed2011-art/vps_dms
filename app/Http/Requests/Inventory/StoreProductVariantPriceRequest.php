<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;

class StoreProductVariantPriceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'priceable_type' => ['required', 'string'],
            'priceable_id' => ['required', 'integer'],
            'cost_price' => ['nullable', 'numeric'],
            'sale_price' => ['nullable', 'numeric'],
            'is_override' => ['boolean'],
        ];
    }
}
