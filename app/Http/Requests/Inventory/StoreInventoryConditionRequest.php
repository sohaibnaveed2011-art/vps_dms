<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'organization_id' => $this->user()->activeContext()->organization_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('inventory_conditions')
                    ->where(fn ($q) => $q->where('organization_id', $this->organization_id)),
            ],
            'is_sellable' => ['nullable', 'boolean'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
