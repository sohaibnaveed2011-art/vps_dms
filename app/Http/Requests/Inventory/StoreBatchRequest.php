<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'batch_number' => [
                'required', 'string', 'max:255',
                Rule::unique('batches')->where(fn ($q) => $q->where('item_id', $this->input('item_id'))),
            ],
            'expiry_date' => ['nullable', 'date'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            if ($this->filled('item_id') && $this->filled('organization_id')) {
                $item = \App\Models\Inventory\Item::find($this->input('item_id'));
                if ($item && (int) $item->organization_id !== (int) $this->input('organization_id')) {
                    $v->errors()->add('item_id', 'Item does not belong to the provided organization.');
                }
            }
        });
    }
}
