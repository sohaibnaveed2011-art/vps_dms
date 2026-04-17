<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'required' ensures the key exists
            // 'array' ensures it's a list
            // 'min:1' ensures at least one file is present
            'images' => 'required|array|min:1', 
            
            // Use 'required' here instead of 'nullable' to ensure 
            // the items in the array are actually valid files.
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
