<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockLocationRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'organization_id' =>
                $this->user()->activeContext()->organization_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'code' => ['nullable','string','max:50'],
            'locatable_type' => ['required','string'],
            'locatable_id' => ['required','integer'],
            'is_active' => ['nullable','boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {

            $type = $this->locatable_type;
            $id = $this->locatable_id;
            $orgId = $this->organization_id;

            if (! class_exists($type)) {
                $v->errors()->add('locatable_type','Invalid locatable type.');
                return;
            }

            $model = $type::find($id);

            if (! $model) {
                $v->errors()->add('locatable_id','Locatable not found.');
                return;
            }

            if ((int)$model->organization_id !== (int)$orgId) {
                $v->errors()->add(
                    'locatable_id',
                    'Locatable must belong to same organization.'
                );
            }
        });
    }
}
