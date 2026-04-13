<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'filled',
                'min:5',
                'max:255',
                'regex:/^[a-zA-Z][a-zA-Z0-9._-]*$/',
                Rule::unique('permissions', 'name')->ignore($this->permission),
            ],
        ];
    }
}
