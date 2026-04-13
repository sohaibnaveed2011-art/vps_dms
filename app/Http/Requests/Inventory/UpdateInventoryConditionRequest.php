<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryConditionRequest extends FormRequest
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
        $id = $this->route('inventory_condition') ?? $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('inventory_conditions')
                    ->where(fn ($q) => $q->where('organization_id', $this->organization_id))
                    ->ignore($id),
            ],
            'is_sellable' => ['sometimes', 'boolean'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
