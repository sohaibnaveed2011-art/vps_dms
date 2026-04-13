<?php

namespace App\Http\Requests\Core;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreOutletSectionRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'organization_id' => ['required','integer','exists:organizations,id'],
            'outlet_id' => ['required','integer','exists:outlets,id'],
            'name' => [
                'required','string','max:255',
                Rule::unique('outlet_sections')->where(fn($q)=> $q->where('outlet_id', $this->input('outlet_id'))),
            ],
            'code' => ['nullable','string','max:100'],
            'is_pos_counter' => ['nullable','boolean'],
            'display_order' => ['nullable','integer'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
