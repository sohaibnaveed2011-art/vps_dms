<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required','integer','exists:product_variants,id'],
            'batch_number' => [
                'required',
                'string',
                Rule::unique('inventory_batches')
                    ->where(fn($q) =>
                        $q->where('product_variant_id', $this->product_variant_id)
                    )
            ],
            'manufacturing_date' => ['nullable','date'],
            'expiry_date' => ['nullable','date'],
            'initial_cost' => ['required','numeric','min:0'],
            'mrp' => ['nullable','numeric','min:0'],
            'warranty_months' => ['nullable','integer','min:0'],
            'storage_condition' => ['nullable','string'],
            'is_recalled' => ['nullable','boolean'],
            'recall_reason' => ['nullable','string'],
        ];
    }
}
