<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // add permission checks if required
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
            Rule::unique('units')
                ->where(fn ($q) =>
                    $q->where('organization_id', $this->organization_id)
                ),
        ],
        'short_name' => [
            'required',
            'string',
            'max:50',
            Rule::unique('units')
                ->where(fn ($q) =>
                    $q->where('organization_id', $this->organization_id)
                ),
        ],
        'allow_decimal' => ['nullable', 'boolean'],
    ];
}

}
