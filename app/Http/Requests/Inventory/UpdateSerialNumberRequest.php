<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSerialNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_batch_id' => ['nullable', 'integer', 'exists:inventory_batches,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:available,sold,returned'],
        ];
    }
}

