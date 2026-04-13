<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateVariationValueRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // variations/{variation}/values/{value}
        $variationId = $this->route('variation');
        $valueId = $this->route('value'); 

        return [
            'value' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                // Unique to this variation, but ignore the current record ID
                Rule::unique('variation_values', 'value')
                    ->where(fn ($q) => $q->where('variation_id', $variationId))
                    ->ignore($valueId),
            ],
            
            'color_code' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.unique' => 'This value already exists for the selected variation.',
        ];
    }
}