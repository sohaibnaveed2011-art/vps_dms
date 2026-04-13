<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'nullable|string|unique:organizations,name,'.$id,
            'legal_name' => 'nullable|string',
            'business_start_date' => 'nullable|date',
            'ntn' => 'nullable|string',
            'strn' => 'nullable|string',
            'incorporation_no' => 'nullable|string',
            'email' => 'nullable|email|unique:organizations,email,'.$id,
            'contact_no' => 'nullable|string',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'longitude' => 'nullable|string',
            'latitude' => 'nullable|string',
            'currency_code' => 'nullable|string|size:3',
            'is_active' => 'boolean',
        ];
    }
}
