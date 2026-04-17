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
            // Used for the reorder function (Bulk update)
            'ordered_ids' => ['sometimes', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'exists:product_images,id'],
        ];
    }
}
