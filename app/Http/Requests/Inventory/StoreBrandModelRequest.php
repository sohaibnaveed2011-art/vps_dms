<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreBrandModelRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Retrieve brandId from the route parameter: /brands/{brandId}/models
        $brandId = $this->route('brand'); 

        return [
            'models' => ['required', 'array', 'min:1'],
            
            'models.*.name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brand_models', 'name')
                    ->where(fn ($q) => $q->where('brand_id', $brandId))
            ],

            'models.*.slug'      => ['nullable', 'string', 'max:255'],
            'models.*.series'    => ['nullable', 'string', 'max:255'],
            'models.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
