<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Validation\Rule;
use App\Models\Inventory\BrandModel;
use App\Http\Requests\BaseFormRequest;

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
            'brand_model_id' => [
                'nullable', 
                'integer', 
                'exists:brand_models,id',
                function ($attribute, $value, $fail) {
                    // Ensure brand_model belongs to selected brand
                    if ($this->has('brand_id') && $value) {
                        $exists = BrandModel::where('id', $value)
                            ->where('brand_id', $this->brand_id)
                            ->exists();
                        
                        if (!$exists) {
                            $fail('The selected brand model does not belong to the selected brand.');
                        }
                    }
                }
            ],
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

            // ADD BRAND MODEL ID FOR VARIANTS
            'variants.*.brand_model_id' => [
                'nullable',
                'integer',
                'exists:brand_models,id',
                function ($attribute, $value, $fail) {
                    // Extract the variant index to get brand_id if needed
                    preg_match('/variants\.(\d+)\.brand_model_id/', $attribute, $matches);
                    $variantIndex = $matches[1] ?? null;
                    
                    if ($variantIndex !== null) {
                        // Check if variant has its own brand_id
                        $variantBrandId = $this->input("variants.{$variantIndex}.brand_id");
                        
                        // If variant has explicit brand_id, use that
                        if ($variantBrandId) {
                            $exists = BrandModel::where('id', $value)
                                ->where('brand_id', $variantBrandId)
                                ->exists();
                            
                            if (!$exists) {
                                $fail("The selected brand model for variant {$variantIndex} does not belong to its brand.");
                            }
                        } 
                        // Otherwise, check against product's brand_id
                        elseif ($this->has('brand_id') && $this->brand_id) {
                            $exists = BrandModel::where('id', $value)
                                ->where('brand_id', $this->brand_id)
                                ->exists();
                            
                            if (!$exists) {
                                $fail("The selected brand model for variant {$variantIndex} does not belong to the product's brand.");
                            }
                        }
                    }
                }
            ],
            
            // OPTIONAL: Allow variants to override brand_id
            'variants.*.brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
                function ($attribute, $value, $fail) {
                    // Ensure if variant changes brand, it's valid for the organization
                    // Add any additional business logic here
                }
            ],

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
    
    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Auto-inherit brand_model_id from product if not specified at variant level
        if ($this->has('variants') && is_array($this->variants)) {
            $variants = $this->variants;
            $productBrandModelId = $this->input('brand_model_id');
            
            foreach ($variants as $key => $variant) {
                // If variant doesn't have brand_model_id, inherit from product
                if (empty($variant['brand_model_id']) && $productBrandModelId) {
                    $variants[$key]['brand_model_id'] = $productBrandModelId;
                }
                
                // If variant doesn't have brand_id, inherit from product
                if (empty($variant['brand_id']) && $this->has('brand_id')) {
                    $variants[$key]['brand_id'] = $this->brand_id;
                }
            }
            
            $this->merge(['variants' => $variants]);
        }
    }
    
    public function messages(): array
    {
        return [
            'variants.*.brand_model_id.exists' => 'Selected brand model for variant does not exist.',
            'variants.*.brand_id.exists' => 'Selected brand for variant does not exist.',
            // ... other messages
        ];
    }
}