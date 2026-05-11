<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseFormRequest;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\BrandModel;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variant = $this->route('variant');
        $variantId = is_object($variant) ? $variant->id : (int) $variant;
        
        $orgId = $this->input('organization_id') 
            ?? optional($variant)->organization_id 
            ?? ProductVariant::where('id', $variantId)->value('organization_id');

        return [
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')
                    ->where(fn ($q) => $q->where('organization_id', $orgId))
                    ->ignore($variantId, 'id'),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_variants', 'barcode')
                    ->where(fn ($q) => $q->where('organization_id', $orgId))
                    ->ignore($variantId, 'id'),
            ],

            'cost_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_serial_tracked' => ['boolean'],
            'is_active' => ['boolean'],
            
            // Only brand_model_id, no brand_id
            'brand_model_id' => [
                'nullable',
                'integer',
                'exists:brand_models,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // Get the variant's product
                        $variantId = $this->route('variant');
                        $variant = ProductVariant::find($variantId);
                        
                        if ($variant && $variant->product && $variant->product->brand_id) {
                            $exists = BrandModel::where('id', $value)
                                ->where('brand_id', $variant->product->brand_id)
                                ->exists();
                            
                            if (!$exists) {
                                $fail("The selected brand model does not belong to the product's brand.");
                            }
                        }
                    }
                }
            ],

            'units' => [
                'sometimes',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (is_array($value)) {
                        $baseUnits = collect($value)->where('is_base', true)->count();
                        if ($baseUnits !== 1) {
                            $fail("The variant must have exactly one base unit.");
                        }
                    }
                }
            ],

            'units.*.id' => ['nullable', 'integer', 'exists:variant_units,id'],
            'units.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'units.*.conversion_factor' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
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

            'units.*.is_base' => ['boolean'],
            'units.*.is_purchase_unit' => ['boolean'],
            'units.*.is_sale_unit' => ['boolean'],

            'variation_value_ids' => ['nullable', 'array'],
            'variation_value_ids.*' => ['integer', 'exists:variation_values,id'],
            
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'SKU must be unique within the organization.',
            'barcode.unique' => 'Barcode must be unique within the organization.',
            'brand_model_id.exists' => 'Selected brand model does not exist.',
        ];
    }
}