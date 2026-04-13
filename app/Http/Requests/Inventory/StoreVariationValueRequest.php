<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreVariationValueRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /**
         * Access {variation} from the route: variations/1/values
         * Laravel automatically parses this from the URL.
         */
        $variationId = $this->route('variation');

        return [
            'values' => ['required', 'array', 'min:1'],

            'values.*.value' => [
                'required',
                'string',
                'max:255',
                // Scoped uniqueness: value must be unique for this specific variation
                Rule::unique('variation_values', 'value')
                    ->where(fn ($q) => $q->where('variation_id', $variationId))
            ],

            'values.*.color_code' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'values.*.value.unique' => 'The value ":input" has already been added to this variation.',
            'values.*.value.required' => 'A value name is required for all entries.',
        ];
    }
}