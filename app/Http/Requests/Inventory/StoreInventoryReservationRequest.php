<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'reference_type' => ['required', 'string'],
            'reference_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0.000001'],
            'status' => ['nullable', 'in:reserved,released,consumed'],
        ];
    }
}
