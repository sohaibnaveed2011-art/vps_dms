<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
class StoreOrganizationPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_.]+$/'
            ],
            'value' => ['nullable', 'array'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

