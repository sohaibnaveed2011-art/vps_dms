<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('batch') ?? $this->route('id');

        return [
            'organization_id' => ['sometimes', 'required', 'integer', 'exists:organizations,id'],
            'item_id' => ['sometimes', 'required', 'integer', 'exists:items,id'],
            'batch_number' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('batches')->where(fn ($q) => $q->where('item_id', $this->input('item_id') ?? null))->ignore($id),
            ],
            'expiry_date' => ['sometimes', 'nullable', 'date'],
            'cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
