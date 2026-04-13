<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreSerialNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'inventory_batch_id' => ['nullable', 'integer', 'exists:inventory_batches,id'],
            'serial_number' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:available,sold,returned'],
        ];
    }
}

