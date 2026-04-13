<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreBrandRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orgId = $this->input("organization_id");
        $uniqueRule = Rule::unique('brands')->where(fn ($q) => $q->where('organization_id', $orgId));
        return [
            'name' => ['required', 'string', 'max:255', $uniqueRule],
            'slug' => ['required', 'string', 'max:255', $uniqueRule],
        ];
    }
}
