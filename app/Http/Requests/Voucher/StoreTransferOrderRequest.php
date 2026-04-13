<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'organization_id' => $this->user()->activeContext()->organization_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'voucher_type_id' => ['required','exists:voucher_types,id'],
            'source_location_type' => ['required','string'],
            'source_location_id'   => ['required','integer'],
            'destination_location_type' => ['required','string'],
            'destination_location_id'   => ['required','integer'],
            'document_number' => ['required','string','max:100'],
            'items' => ['required','array','min:1'],
            'items.*.product_variant_id' => ['required','exists:product_variants,id'],
            'items.*.inventory_batch_id' => ['nullable','exists:inventory_batches,id'],
            'items.*.quantity' => ['required','numeric','min:0.000001'],
            'items.*.unit_cost' => ['required','numeric','min:0'],
        ];
    }
}
