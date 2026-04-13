<?php

namespace App\Http\Requests\Core;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreSectionCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'organization_id' => ['required','integer','exists:organizations,id'],
            'name' => [
                'required','string','max:255',
                Rule::unique('section_categories')->where(fn($q) => $q->where('organization_id', $this->input('organization_id'))),
            ],
            'description' => ['nullable','string'],
        ];
    }
}
