<?php

namespace App\Http\Requests\Core;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'branch_id'       => ['nullable','integer','exists:branches,id'],
            'name'            => [
                'required','string','max:255',
                // unique per organization
                Rule::unique('warehouses')->where(fn($q) => $q->where('organization_id', $this->input('organization_id'))),
            ],
            'code' => [
                'nullable','string','max:100',
                Rule::unique('warehouses')->where(fn($q) => $q->where('organization_id', $this->input('organization_id'))),
            ],
            'email' => ['nullable','email','max:255'],
            'contact_person' => ['nullable','string','max:150'],
            'contact_no' => ['nullable','string','max:50'],
            'address' => ['nullable','string'],
            'city' => ['nullable','string','max:100'],
            'state' => ['nullable','string','max:100'],
            'country' => ['nullable','string','max:100'],
            'zip_code' => ['nullable','string','max:20'],
            'longitude' => ['nullable','numeric','between:-180,180'],
            'latitude' => ['nullable','numeric','between:-90,90'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
