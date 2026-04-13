<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brandId = $this->route('brand') ?? $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('brands')
                    ->where(fn ($q) =>
                        $q->where('organization_id', $this->organization_id)
                    )
                    ->ignore($brandId),
            ],
        ];
    }
}
