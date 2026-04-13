<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('inventory_batch') ?? $this->route('id');

        return [
            'batch_number' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('inventory_batches')
                    ->where(fn($q) =>
                        $q->where('product_variant_id', $this->product_variant_id)
                    )
                    ->ignore($id),
            ],
            'manufacturing_date' => ['sometimes','nullable','date'],
            'expiry_date' => ['sometimes','nullable','date'],
            'mrp' => ['sometimes','nullable','numeric','min:0'],
            'warranty_months' => ['sometimes','nullable','integer','min:0'],
            'storage_condition' => ['sometimes','nullable','string'],
            'is_recalled' => ['sometimes','nullable','boolean'],
            'recall_reason' => ['sometimes','nullable','string'],
            'status' => ['sometimes','in:open,closed'],
        ];
    }
}
