<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'organization_id' => 'required|integer|exists:organizations,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'outlet_id' => 'nullable|integer|exists:outlets,id',
            'cash_register_id' => 'nullable|integer|exists:cash_registers,id',
        ];
    }
}
