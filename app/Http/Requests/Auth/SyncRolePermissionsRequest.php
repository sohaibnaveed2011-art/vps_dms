<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'filled', 'min:5', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9._-]*$/'],
        ];
    }
}
