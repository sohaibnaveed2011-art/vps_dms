<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // if token present, it's reset; otherwise this request could be reused but controller uses separate requests
        if ($this->filled('token')) {
            return [
                'token' => ['required','string'],
                'email' => ['required','email','exists:users,email'],
                'password' => ['required','string','min:8','confirmed'],
            ];
        }

        return [
            'email' => ['required','email','exists:users,email'],
        ];
    }
}
