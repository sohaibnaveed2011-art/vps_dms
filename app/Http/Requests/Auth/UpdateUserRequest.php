<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ?? null;

        return [
            'name' => ['nullable','string','max:255'],
            'email' => ['nullable','string','email','max:255', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable','string','min:8','confirmed'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
