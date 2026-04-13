<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'organization_id' => $this->user()->activeContext()->organization_id,
            'brand_id' => $this->route('brand'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('brand_model') ?? $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('brand_models')
                    ->where(fn ($q) =>
                        $q->where('brand_id', $this->brand_id)
                    )
                    ->ignore($id),
            ],

            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'series' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
