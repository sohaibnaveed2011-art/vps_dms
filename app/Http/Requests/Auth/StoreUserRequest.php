<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'email' => ['required','string','email','max:255','unique:users'],
            'password' => ['required','string','min:8','confirmed'],
            'is_active' => ['nullable','boolean'],
            'assign_default_role' => ['nullable','string'],
            'context' => ['nullable','array'],
            'assignments' => ['nullable','array'],
        ];
    }
}
