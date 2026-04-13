<?php

namespace App\Http\Requests\Core;

use App\Http\Requests\BaseFormRequest;


class StoreBranchRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:150',
            'contact_no' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',
            'is_fbr_active' => 'nullable|boolean',
            'pos_id' => 'nullable|string|max:255',
            'pos_auth_token' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];

    }
}
