<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\StockLocation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStockLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'organization_id' => $this->user()
                ?->activeContext()
                ?->organization_id,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('stock_location')
            ?? $this->route('stockLocation')
            ?? $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'locatable_type' => [
                'sometimes',
                'required',
                'string',
            ],

            'locatable_id' => [
                'sometimes',
                'required',
                'integer',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {

            $id = $this->route('stock_location')
                ?? $this->route('stockLocation')
                ?? $this->route('id');

            $orgId = $this->organization_id;

            // -------------------------------------------------
            // Ensure record belongs to organization
            // -------------------------------------------------
            if ($id) {
                $existing = StockLocation::withTrashed()->find($id);

                if (! $existing) {
                    $v->errors()->add('id', 'Stock location not found.');
                    return;
                }

                if ((int) $existing->organization_id !== (int) $orgId) {
                    $v->errors()->add('id', 'Unauthorized stock location.');
                }
            }

            // -------------------------------------------------
            // Ensure locatable belongs to same organization
            // -------------------------------------------------
            if ($this->filled('locatable_type') && $this->filled('locatable_id')) {

                $modelClass = $this->input('locatable_type');
                $locatableId = $this->input('locatable_id');

                if (! class_exists($modelClass)) {
                    $v->errors()->add('locatable_type', 'Invalid locatable type.');
                    return;
                }

                $locatable = $modelClass::find($locatableId);

                if (! $locatable) {
                    $v->errors()->add('locatable_id', 'Invalid locatable id.');
                    return;
                }

                if (property_exists($locatable, 'organization_id')
                    && (int) $locatable->organization_id !== (int) $orgId) {
                    $v->errors()->add(
                        'locatable_id',
                        'Locatable must belong to the same organization.'
                    );
                }
            }
        });
    }
}
