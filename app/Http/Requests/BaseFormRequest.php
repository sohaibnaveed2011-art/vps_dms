<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseFormRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->user()?->is_admin) {
            $this->merge([
                'organization_id' => $this->user()
                    ->activeContext()
                    ->organization_id,
            ]);
        }
    }

    protected function organizationId(): int
    {
        return $this->input('organization_id');
    }
}
