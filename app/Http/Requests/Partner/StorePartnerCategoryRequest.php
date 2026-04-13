<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => [
                'required','string','max:255',
                // unique per organization + type
                Rule::unique('partner_categories')->where(fn($q) => $q->where('organization_id', $this->input('organization_id'))->where('type', $this->input('type'))),
            ],
            'type' => ['required','in:customer,supplier'],
            'is_active' => ['nullable','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This category name already exists for the chosen organization and type.',
        ];
    }
}
