<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $supplier = $this->route('supplier') ?? $this->route('id');
        $supplierId = is_object($supplier) ? $supplier->id : $supplier;
        $orgId = $this->input("organization_id");
        $uniqueRule = function () use ($supplierId, $orgId) {
            return Rule::unique('suppliers')->where(fn ($q) => $q->where('organization_id', $orgId))->ignore($supplierId);
        };
        return [
            'partner_category_id' => ['nullable','integer','exists:partner_categories,id'],
            'name' => ['sometimes','required','string','max:255', $uniqueRule()],
            'cnic' => ['nullable','string','max:50', $uniqueRule()],
            'ntn' => ['nullable','string','max:50', $uniqueRule()],
            'strn' => ['nullable','string','max:50', $uniqueRule()],
            'incorporation_no' => ['nullable','string','max:100', $uniqueRule()],
            'contact_person' => ['nullable','string','max:150'],
            'contact_no' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255', $uniqueRule()],
            'address' => ['nullable','string'],
            'longitude' => ['nullable','numeric','between:-180,180'],
            'latitude' => ['nullable','numeric','between:-90,90'],
            'payment_terms_days' => ['nullable','integer','min:0'],
            'current_balance' => ['nullable','numeric'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
