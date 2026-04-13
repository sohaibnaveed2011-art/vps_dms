<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) ? $product->id : (int) $product;

        $orgId = $this->input('organization_id') 
            ?? optional($product)->organization_id 
            ?? Product::where('id', $productId)->value('organization_id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')
                    ->where(fn ($q) => $q->where('organization_id', $orgId))
                    ->ignore($productId, 'id'),
            ],

            'category_id'      => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id'         => ['nullable', 'integer', 'exists:brands,id'],
            'tax_id'           => ['nullable', 'integer', 'exists:taxes,id'],
            'description'      => ['nullable', 'string'],
            'valuation_method' => ['sometimes', 'required', Rule::in(['FIFO', 'FEFO', 'WAVG'])],
            'has_warranty'     => ['boolean'],
            'warranty_months'  => ['nullable', 'integer', 'min:1'],
            'has_variants'     => ['boolean'],
            'is_active'        => ['boolean'],
            
            /* -------------------------------------------------
                | PRODUCT VARIATIONS (Pivot Table Relations)
                ------------------------------------------------- */
            'variation_ids'   => ['required_if:has_variants,true', 'array'],
            'variation_ids.*' => ['integer', 'exists:variations,id'],
            
            /* -------------------------------------------------
                | VARIANTS
                ------------------------------------------------- */
            'variants' => ['sometimes', 'required', 'array', 'min:1'],
            
            // Crucial: ID check for existing variants during update
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],

            'variants.*.sku' => [
                'required', 
                'string', 
                'max:255',
                // Note: Unique SKU validation per organization usually happens here too
            ],
            'variants.*.barcode'           => ['nullable', 'string', 'max:255'],
            'variants.*.cost_price'        => ['required', 'numeric', 'min:0'],
            'variants.*.sale_price'        => ['required', 'numeric', 'min:0'],
            'variants.*.is_serial_tracked' => ['boolean'],
            'variants.*.is_active'         => ['boolean'],

            /* -------------------------------------------------
                | UNITS (With logic to prevent 12 becoming 1)
                ------------------------------------------------- */
            'variants.*.units' => [
                'required', 
                'array', 
                'min:1',
                function ($attribute, $value, $fail) {
                    $baseUnits = collect($value)->where('is_base', true)->count();
                    if ($baseUnits !== 1) {
                        $fail("The variant must have exactly one base unit.");
                    }
                }
            ],

            'variants.*.units.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            
            'variants.*.units.*.conversion_factor' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    // Extract the base status of the current unit
                    $isBasePath = str_replace('conversion_factor', 'is_base', $attribute);
                    $isBase = request()->input($isBasePath);

                    if ($isBase && (float)$value !== 1.0) {
                        $fail('The conversion factor for a base unit must be 1.');
                    }

                    if (!$isBase && (float)$value <= 0) {
                        $fail('Conversion factor must be greater than 0.');
                    }
                },
            ],

            'variants.*.units.*.is_base'          => ['boolean'],
            'variants.*.units.*.is_purchase_unit' => ['boolean'],
            'variants.*.units.*.is_sale_unit'     => ['boolean'],

            /* -------------------------------------------------
                | VARIATION VALUES (Pivot for Variant Values)
                ------------------------------------------------- */
            'variants.*.variation_value_ids'   => ['nullable', 'array'],
            'variants.*.variation_value_ids.*' => ['integer', 'exists:variation_values,id'],
            ];
    }
}