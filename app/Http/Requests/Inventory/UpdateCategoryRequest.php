<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // route param when using Route::apiResource('categories', ...) is {category}
        $id = $this->route('category') ?? $this->route('id');

        return [
            'organization_id' => ['sometimes', 'required', 'integer', 'exists:organizations,id'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('categories')->where(fn ($q) => $q->where('organization_id', $this->input('organization_id') ?? $this->route('organization_id')))->ignore($id),
            ],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $orgId = $this->input('organization_id');
            $parentId = $this->input('parent_id');
            $categoryId = $this->route('category') ?? $this->route('id');

            // if parent_id provided, check it belongs to same organization (prefer payload orgId, otherwise resolve from existing record)
            if ($parentId) {
                $parent = Category::find($parentId);
                if (! $parent) {
                    $v->errors()->add('parent_id', 'Parent category not found.');

                    return;
                }

                // Determine org to compare against: payload organization_id if provided, else existing category's organization
                $targetOrgId = $orgId;
                if (! $targetOrgId && $categoryId) {
                    $existing = Category::find($categoryId);
                    $targetOrgId = $existing?->organization_id;
                }

                if ($targetOrgId && ((int) $parent->organization_id !== (int) $targetOrgId)) {
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
