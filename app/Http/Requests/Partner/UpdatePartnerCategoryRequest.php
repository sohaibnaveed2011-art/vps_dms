<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartnerCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('partner_category') ?? $this->route('id');

        return [
            'name' => [
                'sometimes','required','string','max:255',
                Rule::unique('partner_categories')->where(fn($q) => $q->where('organization_id', $this->input('organization_id') ?? $this->route('organization_id'))->where('type', $this->input('type') ?? $this->route('type')))->ignore($id),
            ],
            'type' => ['sometimes','required','in:customer,supplier'],
            'is_active' => ['sometimes','nullable','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This category name already exists for the chosen organization and type.',
        ];
    }
}
