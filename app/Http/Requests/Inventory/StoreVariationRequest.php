<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;
use App\Models\Inventory\Variation;
use Illuminate\Validation\Rule;

class StoreVariationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', // scope uniqueness to organization_id
                Rule::unique('variations')->where(fn ($q) => $q->where('organization_id', $this->input('organization_id'))),
            ],
            'short_name' => [
                'required',
                'string',
                'max:100',
                // scope uniqueness to organization_id
                Rule::unique('variations')->where(fn ($q) => $q->where('organization_id', $this->input('organization_id'))),
            ],
            'has_multiple' => ['boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $orgId = $this->input('organization_id');
            $short = $this->input('short_name');

            if ($short && $orgId) {
                // case-insensitive uniqueness guard
                $exists = Variation::where('organization_id', $orgId)
                    ->whereRaw('LOWER(short_name) = ?', [strtolower($short)])
                    ->exists();

                if ($exists) {
                    $v->errors()->add('short_name', 'This short_name is already used in the organization.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'short_name.unique' => 'This short name is already in use.',
        ];
    }
}
