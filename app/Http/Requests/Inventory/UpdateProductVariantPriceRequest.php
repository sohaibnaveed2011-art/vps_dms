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
            'priceable_type' => ['nullable', 'string'],
            'priceable_id' => ['nullable', 'integer'],
            'cost_price' => ['nullable', 'numeric'],
            'sale_price' => ['nullable', 'numeric'],
        ];
    }
}

