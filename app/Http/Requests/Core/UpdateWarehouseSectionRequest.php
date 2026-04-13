<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseSectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('warehouse_section') ?? $this->route('id');

        return [
            'warehouse_id' => ['sometimes','required','integer','exists:warehouses,id'],
            'parent_section_id' => ['sometimes','nullable','integer','exists:warehouse_sections,id'],
            'section_category_id' => ['sometimes','nullable','integer','exists:section_categories,id'],
            'hierarchy_path' => ['sometimes','nullable','string','max:255'],
            'level' => ['sometimes','nullable','integer','min:1'],
            'name' => ['sometimes','required','string','max:255'],
            'code' => ['sometimes','nullable','string','max:100',
                Rule::unique('warehouse_sections')->where(fn($q)=> $q->where('warehouse_id', $this->input('warehouse_id') ?? $this->route('warehouse_id')))->ignore($id),
            ],
            'zone' => ['sometimes','nullable','string','max:100'],
            'aisle' => ['sometimes','nullable','string','max:100'],
            'rack' => ['sometimes','nullable','string','max:100'],
            'shelf' => ['sometimes','nullable','string','max:100'],
            'bin' => ['sometimes','nullable','string','max:100'],
            'description' => ['sometimes','nullable','string'],
            'is_active' => ['sometimes','nullable','boolean'],
        ];
    }
}
