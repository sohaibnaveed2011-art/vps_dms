<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize incoming values before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('sku')) {
            $this->merge([
                'sku' => strtoupper(trim($this->input('sku'))),
            ]);
        }

        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->input('name')),
            ]);
        }
    }

    /**
     * Resolve item id from route.
     */
    protected function itemId(): int
    {
        $routeItem = $this->route('item') ?? $this->route('id');

        if (is_object($routeItem)) {
            return (int) $routeItem->id;
        }

        return (int) $routeItem;
    }

    /**
     * Resolve organization id (payload → DB fallback).
     */
    protected function organizationId(): int
    {
        if ($this->filled('organization_id')) {
            return (int) $this->input('organization_id');
        }

        return (int) Item::whereKey($this->itemId())->value('organization_id');
    }

    public function rules(): array
    {
        $itemId = $this->itemId();
        $orgId = $this->organizationId();

        return [
            'organization_id' => ['sometimes', 'required', 'integer', 'exists:organizations,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'brand_model_id' => ['sometimes', 'nullable', 'integer', 'exists:brand_models,id'],
            'base_unit_id' => ['sometimes', 'required', 'integer', 'exists:units,id'],

            'name' => ['sometimes', 'required', 'string', 'max:255'],

            // ✅ CORRECT SKU RULE
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('items')
                    ->where(fn ($q) => $q->where('organization_id', $orgId))
                    ->ignore($itemId),
            ],

            'barcode' => ['nullable', 'string', 'max:255'],
            'hs_code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'valuation_method' => ['nullable', Rule::in(['FIFO', 'WAVG'])],
            'alert_quantity' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],

            'is_active' => ['nullable', 'boolean'],

            // -------------------------
            // Nested Units
            // -------------------------
            'units' => ['nullable', 'array'],
            'units.*.id' => ['nullable', 'integer', 'exists:item_units,id'],
            'units.*.unit_id' => ['required_with:units', 'integer', 'exists:units,id'],
            'units.*.conversion_factor' => ['nullable', 'numeric', 'min:0.000001'],
            'units.*.is_base' => ['nullable', 'boolean'],
            'units.*.is_active' => ['nullable', 'boolean'],

            // -------------------------
            // Variation Values
            // -------------------------
            'variation_values' => ['nullable', 'array'],
            'variation_values.*.variation_value_id' => [
                'required_with:variation_values',
                'integer',
                'exists:variation_values,id',
            ],
        ];
    }

    /**
     * Cross-entity integrity checks.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {

            $orgId = $this->organizationId();

            // Brand → Organization
            if ($this->filled('brand_id')) {
                $brand = \App\Models\Inventory\Brand::find($this->brand_id);
                if ($brand && (int) $brand->organization_id !== $orgId) {
                    $v->errors()->add('brand_id', 'Brand does not belong to this organization.');
                }
            }

            // Brand Model → Brand
            if ($this->filled('brand_model_id') && $this->filled('brand_id')) {
                $model = \App\Models\Inventory\BrandModel::find($this->brand_model_id);
                if ($model && (int) $model->brand_id !== (int) $this->brand_id) {
                    $v->errors()->add('brand_model_id', 'Brand model does not belong to the selected brand.');
                }
            }

            // Units → Organization
            if ($this->filled('units')) {
                foreach ($this->units as $idx => $row) {
                    $unit = \App\Models\Inventory\Unit::find($row['unit_id'] ?? null);
                    if ($unit && (int) $unit->organization_id !== $orgId) {
                        $v->errors()->add("units.$idx.unit_id", 'Unit must belong to the same organization.');
                    }
                }
            }
        });
    }
}
