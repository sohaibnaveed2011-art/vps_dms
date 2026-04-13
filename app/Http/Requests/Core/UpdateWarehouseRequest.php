<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // route parameter name: {warehouse}
        $warehouseId = $this->route('warehouse') ?? $this->route('id');

        return [
            'branch_id'       => ['sometimes','nullable','integer','exists:branches,id'],
            'name' => [
                'sometimes','required','string','max:255',
                Rule::unique('warehouses')->where(fn($q) => $q->where('organization_id', $this->input('organization_id') ?? $this->route('organization_id')))->ignore($warehouseId),
            ],
            'code' => [
                'sometimes','nullable','string','max:100',
                Rule::unique('warehouses')->where(fn($q) => $q->where('organization_id', $this->input('organization_id') ?? $this->route('organization_id')))->ignore($warehouseId),
            ],
            'email' => ['sometimes','nullable','email','max:255'],
            'contact_person' => ['sometimes','nullable','string','max:150'],
            'contact_no' => ['sometimes','nullable','string','max:50'],
            'address' => ['sometimes','nullable','string'],
            'city' => ['sometimes','nullable','string','max:100'],
            'state' => ['sometimes','nullable','string','max:100'],
            'country' => ['sometimes','nullable','string','max:100'],
            'zip_code' => ['sometimes','nullable','string','max:20'],
            'longitude' => ['sometimes','nullable','numeric','between:-180,180'],
            'latitude' => ['sometimes','nullable','numeric','between:-90,90'],
            'is_active' => ['sometimes','nullable','boolean'],
        ];
    }
}
