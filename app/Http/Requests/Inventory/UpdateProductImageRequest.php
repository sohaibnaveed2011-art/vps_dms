<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_primary' => ['sometimes','boolean'],
            'sort_order' => ['sometimes','integer','min:1'],
        ];
    }
}
