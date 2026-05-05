<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Skip if running in console
        if ($this->isRunningInConsole()) {
            return;
        }

        $user = $this->user();
        
        // For admin users, they can specify organization_id
        // For non-admin, force their active organization
        if ($user && !$user->is_admin) {
            $activeContext = $user->activeContext();
            
            if ($activeContext && $activeContext->organization_id) {
                $this->merge([
                    'organization_id' => $activeContext->organization_id,
                ]);
            }
        }
    }

    /**
     * Get the organization ID from the request.
     */
    protected function organizationId(): ?int
    {
        return $this->input('organization_id');
    }

    /**
     * Get the authenticated user with null safety.
     */
    protected function getCurrentUser()
    {
        return $this->user();
    }

    /**
     * Check if running in console.
     */
    protected function isRunningInConsole(): bool
    {
        return app()->runningInConsole();
    }

    /**
     * Common validation rules for organization-scoped requests.
     */
    protected function organizationRules(): array
    {
        return [
            'organization_id' => [
                'required',
                'integer',
                'exists:organizations,id',
                function ($attribute, $value, $fail) {
                    $user = $this->user();
                    
                    // Non-admin users can only use their own organization
                    if ($user && !$user->is_admin) {
                        $activeContext = $user->activeContext();
                        if ($activeContext && $activeContext->organization_id != $value) {
                            $fail('You do not have access to this organization.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Common validation rules for soft deletes.
     */
    protected function withTrashedRules(): array
    {
        return [
            'with_trashed' => ['sometimes', 'boolean'],
            'only_trashed' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Common validation attributes.
     */
    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
            'with_trashed' => 'include deleted records',
            'only_trashed' => 'only deleted records',
        ];
    }

    /**
     * Common validation messages.
     */
    public function messages(): array
    {
        return [
            'organization_id.required' => 'Organization is required.',
            'organization_id.exists' => 'Selected organization does not exist.',
        ];
    }
}