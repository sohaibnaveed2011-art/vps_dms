<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Validation\Rule;
use App\Models\Inventory\Product;
use App\Models\Inventory\BrandModel;
use Illuminate\Foundation\Http\FormRequest;

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

            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            
            'brand_model_id' => [
                'nullable',
                'integer',
                'exists:brand_models,id',
                function ($attribute, $value, $fail) {
                    // Only validate relationship if both brand_id and brand_model_id are provided
                    if ($this->has('brand_id') && $this->brand_id && $value) {
                        $exists = BrandModel::where('id', $value)
                            ->where('brand_id', $this->brand_id)
                            ->exists();
                        
                        if (!$exists) {
                            $fail('The selected brand model does not belong to the selected brand.');
                        }
                    }
                }
            ],
            
            'tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'description' => ['nullable', 'string'],
            'valuation_method' => ['sometimes', 'required', Rule::in(['FIFO', 'FEFO', 'WAVG'])],
            'has_warranty' => ['boolean'],
            'warranty_months' => ['nullable', 'integer', 'min:1'],
            'has_variants' => ['boolean'],
            'is_active' => ['boolean'],
            
            /* -------------------------------------------------
                | PRODUCT VARIATIONS (Pivot Table Relations)
                ------------------------------------------------- */
            'variation_ids' => ['required_if:has_variants,true', 'array'],
            'variation_ids.*' => ['integer', 'exists:variations,id'],
            
            /* -------------------------------------------------
                | VARIANTS
                ------------------------------------------------- */
            'variants' => ['sometimes', 'required', 'array', 'min:1'],
            
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.sku' => ['required', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            
            // Variant brand model (independent from product)
            'variants.*.brand_model_id' => [
                'nullable',
                'integer',
                'exists:brand_models,id',
                function ($attribute, $value, $fail) {
                    // Extract variant index
                    preg_match('/variants\.(\d+)\.brand_model_id/', $attribute, $matches);
                    $variantIndex = $matches[1] ?? null;
                    
                    if ($variantIndex !== null && $value) {
                        // Check if variant has its own brand_id
                        $variantBrandId = $this->input("variants.{$variantIndex}.brand_id");
                        
                        if ($variantBrandId) {
                            $exists = BrandModel::where('id', $value)
                                ->where('brand_id', $variantBrandId)
                                ->exists();
                            
                            if (!$exists) {
                                $fail("The selected brand model does not belong to the variant's brand.");
                            }
                        }
                    }
                }
            ],
            
            'variants.*.cost_price' => ['required', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['required', 'numeric', 'min:0'],
            'variants.*.is_serial_tracked' => ['boolean'],
            'variants.*.is_active' => ['boolean'],

            /* -------------------------------------------------
                | UNITS
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

            'variants.*.units.*.id' => ['nullable', 'integer', 'exists:variant_units,id'],
            'variants.*.units.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            
            'variants.*.units.*.conversion_factor' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
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

            'variants.*.units.*.is_base' => ['boolean'],
            'variants.*.units.*.is_purchase_unit' => ['boolean'],
            'variants.*.units.*.is_sale_unit' => ['boolean'],

            /* -------------------------------------------------
                | VARIATION VALUES
                ------------------------------------------------- */
            'variants.*.variation_value_ids' => ['nullable', 'array'],
            'variants.*.variation_value_ids.*' => ['integer', 'exists:variation_values,id'],
            
            /* -------------------------------------------------
                | VARIANT IMAGES
                ------------------------------------------------- */
            'variants.*.images' => 'nullable|array',
            'variants.*.images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}