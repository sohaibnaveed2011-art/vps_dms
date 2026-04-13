<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['nullable', 'numeric', 'min:0.000001'],
            'status' => ['nullable', 'in:reserved,released,consumed'],
        ];
    }
}

