<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;
use App\Models\Inventory\Category;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true; // change to permission checks if required
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                // unique per organization
                Rule::unique('categories')->where(fn ($q) => $q->where('organization_id', $this->input('organization_id'))),
            ],
            'slug' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $orgId = $this->input('organization_id');
            $parentId = $this->input('parent_id');

            if ($parentId && $orgId) {
                $parent = Category::find($parentId);
                if (! $parent) {
                    $v->errors()->add('parent_id', 'Parent category not found.');
                } elseif ((int) $parent->organization_id !== (int) $orgId) {
                    $v->errors()->add('parent_id', 'Parent category must belong to the same organization.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A category with this name already exists in the organization.',
            'parent_id.exists' => 'The provided parent category does not exist.',
        ];
    }
}
