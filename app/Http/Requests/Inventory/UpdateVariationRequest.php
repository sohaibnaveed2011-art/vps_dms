<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Variation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('variation') ?? $this->route('id');

        // Organization may be provided to re-scope uniqueness; fallback to existing model org if not provided
        $orgId = $this->input('organization_id');
        if (! $orgId && $id) {
            $existing = Variation::find($id);
            $orgId = $existing?->organization_id;
        }

        return [
            'organization_id' => ['sometimes', 'required', 'integer', 'exists:organizations,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'short_name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('variations')->where(fn ($q) => $q->where('organization_id', $orgId))->ignore($id),
            ],
            'has_multiple' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $id = $this->route('variation') ?? $this->route('id');
            $orgId = $this->input('organization_id');

            // If short_name provided, perform case-insensitive collision check
            if ($this->filled('short_name')) {
                $short = strtolower(trim($this->input('short_name')));

                $query = Variation::whereRaw('LOWER(short_name) = ?', [$short]);

                if ($orgId) {
                    $query->where('organization_id', $orgId);
                } elseif ($id) {
                    $query->where('organization_id', Variation::find($id)?->organization_id);
                }

                if ($id) {
                    $query->where('id', '!=', $id);
                }

                if ($query->exists()) {
                    $v->errors()->add('short_name', 'This short_name is already used in the organization.');
                }
            }

            // If parent org provided, no extra checks needed here for Variation, but could add cross-checks if required.
        });
    }

    public function messages(): array
    {
        return [
            'short_name.unique' => 'This short_name is already used in the organization.',
        ];
    }
}
