<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route param for apiResource is {unit}
        $id = $this->route('unit') ?? $this->route('id');

        return [
            'organization_id' => ['sometimes', 'required', 'integer', 'exists:organizations,id'],
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('units')->where(fn ($q) => $q->where('organization_id', $this->input('organization_id') ?? $this->route('organization_id')))->ignore($id),
            ],
            'short_name' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('units')->where(fn ($q) => $q->where('organization_id', $this->input('organization_id') ?? $this->route('organization_id')))->ignore($id),
            ],
            'allow_decimal' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $id = $this->route('unit') ?? $this->route('id');
            $orgId = $this->input('organization_id');
            // If organization_id provided but unit exists and belongs to different org -> error
            if ($orgId && $id) {
                $unit = Unit::find($id);
                if ($unit && (int) $unit->organization_id !== (int) $orgId) {
                    $v->errors()->add('organization_id', 'Provided organization does not match existing unit.');
                }
            }

            // Additional normalization check for short_name collisions (case-insensitive)
            if ($this->filled('short_name')) {
                $normalized = strtolower(trim($this->input('short_name')));
                $q = Unit::whereRaw('LOWER(short_name) = ?', [$normalized]);
                if ($this->input('organization_id')) {
                    $q->where('organization_id', $this->input('organization_id'));
                } elseif ($id) {
                    $q->where('organization_id', Unit::find($id)?->organization_id);
                }
                if ($id) {
                    $q->where('id', '!=', $id);
                }
                if ($q->exists()) {
                    $v->errors()->add('short_name', 'This short_name is already used in the organization.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A unit with this name already exists for the organization.',
            'short_name.unique' => 'This short_name is already used in the organization.',
        ];
    }
}
