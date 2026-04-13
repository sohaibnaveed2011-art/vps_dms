<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],

            // polymorphic source/destination; keep strings for type and ids for reference
            'source_section_type' => ['nullable', 'string'],
            'source_section_id' => ['nullable', 'integer'],
            'destination_section_type' => ['nullable', 'string'],
            'destination_section_id' => ['nullable', 'integer'],

            'reference_type' => ['nullable', 'string'],
            'reference_id' => ['nullable', 'integer'],

            'type' => ['required', Rule::in(['in', 'out', 'transfer', 'adjustment'])],
            'quantity' => ['required', 'numeric', 'min:0.000001'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'running_balance' => ['nullable', 'numeric'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            // basic sanity: for transfer, both source and destination should be present
            $type = $this->input('type');

            if ($type === 'transfer') {
                if (empty($this->input('source_section_type')) || empty($this->input('source_section_id'))) {
                    $v->errors()->add('source_section_id', 'transfer requires a source section.');
                }
                if (empty($this->input('destination_section_type')) || empty($this->input('destination_section_id'))) {
                    $v->errors()->add('destination_section_id', 'transfer requires a destination section.');
                }
            }

            // For in/out/adjustment at least one of source or destination must be provided
            if (in_array($type, ['in', 'out', 'adjustment'])) {
                if (empty($this->input('source_section_type')) && empty($this->input('destination_section_type'))) {
                    $v->errors()->add('source_section_type', 'Either source or destination section must be provided.');
                }
            }

            // validate organization & item relation
            if ($this->filled('item_id') && $this->filled('organization_id')) {
                $item = \App\Models\Inventory\Item::find($this->input('item_id'));
                if ($item && (int) $item->organization_id !== (int) $this->input('organization_id')) {
                    $v->errors()->add('item_id', 'Item does not belong to provided organization.');
                }
            }
        });
    }
}
