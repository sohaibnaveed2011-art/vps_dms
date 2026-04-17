<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /* -------------------------------------------------
             | PRODUCT
             ------------------------------------------------- */

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')
                    ->where(fn ($q) =>
                        $q->where('organization_id', $this->input('organization_id'))
                    )
            ],

            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id'    => ['nullable', 'integer', 'exists:brands,id'],
            'tax_id'      => ['nullable', 'integer', 'exists:taxes,id'],
            'description' => ['nullable', 'string'],
            'valuation_method' => ['required', Rule::in(['FIFO','FEFO','WAVG'])],
            'has_warranty' => ['boolean'],
            'warranty_months' => ['nullable','integer','min:1'],
            'has_variants' => ['boolean'],
            'is_active' => ['boolean'],
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            
            /* -------------------------------------------------
            | PRODUCT VARIATIONS (Attributes used by this product)
            ------------------------------------------------- */
            'variation_ids' => ['required_if:has_variants,true', 'array'],
            'variation_ids.*' => ['integer', 'exists:variations,id'],
            
            /* -------------------------------------------------
             | VARIANTS
             ------------------------------------------------- */

            'variants' => ['required','array','min:1'],

            'variants.*.sku' => ['required', 'string', 'max:255'],

            'variants.*.barcode' => ['nullable','string','max:255'],

            'variants.*.cost_price' => ['required','numeric','min:0'],
            'variants.*.sale_price' => ['required','numeric','min:0'],
            'variants.*.is_serial_tracked' => ['boolean'],
            'variants.*.is_active' => ['boolean'],

            /* -------------------------------------------------
             | UNITS
             ------------------------------------------------- */

            'variants.*.units' => ['required','array','min:1'],

            'variants.*.units.*.unit_id' => ['required', 'integer', 'exists:units,id'],

            'variants.*.units.*.conversion_factor' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    // Get the path to the current unit being validated
                    // e.g., "variants.0.units.1.conversion_factor" -> "variants.0.units.1.is_base"
                    $isBasePath = str_replace('conversion_factor', 'is_base', $attribute);
                    $isBase = request()->input($isBasePath);

                    if ($isBase && (float)$value !== 1.0) {
                        $fail('The conversion factor for a base unit must be exactly 1.');
                    }

                    if (!$isBase && (float)$value <= 0) {
                        $fail('The conversion factor for non-base units must be greater than 0.');
                    }
                },
            ],

            'variants.*.units.*.is_base' => ['boolean'],
            'variants.*.units.*.is_purchase_unit' => ['boolean'],
            'variants.*.units.*.is_sale_unit' => ['boolean'],

            /* -------------------------------------------------
             | VARIATION VALUES
             ------------------------------------------------- */

            'variants.*.variation_value_ids' => ['nullable', 'array'],
            'variants.*.variation_value_ids.*' => ['integer', 'exists:variation_values,id'],
            /* -------------------------------------------------
             | VARIATION IMAGES
             ------------------------------------------------- */
            'variants.*.images' => 'nullable|array',
            'variants.*.images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
