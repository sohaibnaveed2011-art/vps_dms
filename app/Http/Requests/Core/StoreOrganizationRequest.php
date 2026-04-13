<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required unique name
            'name' => 'required|string|max:255|unique:organizations,name',

            // Optional fields
            'legal_name' => 'nullable|string|max:255',
            'business_start_date' => 'nullable|date',

            // Registration numbers
            'ntn' => 'nullable|string|max:50',
            'strn' => 'nullable|string|max:50',
            'incorporation_no' => 'nullable|string|max:100',

            // Email is NOT unique globally (as per your schema)
            'email' => 'nullable|email|max:255',

            // Contact fields
            'contact_no' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',

            // Address
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',

            // Location — numeric as per schema (decimal)
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',

            // Business metadata
            'currency_code' => 'nullable|string|size:3|uppercase',

            // Status flag
            'is_active' => 'boolean',
            'policy_locked' => 'boolean',

            // File uploads (optional)
            // You can enable these if you accept file uploads:
            // 'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            // 'favicon' => 'nullable|image|mimes:ico,png|max:1024',
        ];
    }

    public function messages(): array
    {
        return [
            'currency_code.uppercase' => 'Currency code must be in uppercase (e.g., PKR, USD).',
        ];
    }
}
