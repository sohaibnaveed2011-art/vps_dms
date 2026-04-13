<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransferOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'sometimes|required|exists:organizations,id',
            'document_type' => 'nullable|string',
            'document_id' => 'nullable|integer',
            'source_location_type' => 'sometimes|required|string',
            'source_location_id' => 'sometimes|required|integer',
            'destination_location_type' => 'sometimes|required|string',
            'destination_location_id' => 'sometimes|required|integer',
            'document_number' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:draft,requested,approved,in_transit,completed,rejected',
            'total_quantity' => 'sometimes|required|numeric|min:0',
            'grand_total_value' => 'sometimes|required|numeric|min:0',
            'requested_by' => 'sometimes|required|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
        ];
    }
}
