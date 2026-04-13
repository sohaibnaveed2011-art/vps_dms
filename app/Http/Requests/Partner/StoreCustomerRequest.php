<?php

namespace App\Http\Requests\Partner;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orgId = $this->input("organization_id");
        $uniqueRule = Rule::unique('customers')->where(fn ($q) => $q->where('organization_id', $orgId));

        return [
            'partner_category_id' => ['nullable','integer','exists:partner_categories,id'],
            'name' => ['required','string','max:255', $uniqueRule],
            'cnic' => ['nullable','string','max:50', $uniqueRule],
            'ntn' => ['nullable','string','max:50', $uniqueRule],
            'strn' => ['nullable','string','max:50', $uniqueRule],
            'incorporation_no' => ['nullable','string','max:100', $uniqueRule],
            'contact_person' => ['nullable','string','max:150'],
            'contact_no' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255', $uniqueRule],
            'address' => ['nullable','string'],
            'longitude' => ['nullable','numeric','between:-180,180'],
            'latitude' => ['nullable','numeric','between:-90,90'],
            'credit_limit' => ['nullable','numeric','min:0'],
            'payment_terms_days' => ['nullable','integer','min:0'],
            'current_balance' => ['nullable','numeric'],
            'is_active' => ['boolean'],
        ];
    }
}
