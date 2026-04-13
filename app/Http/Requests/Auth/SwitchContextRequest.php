<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Auth\UserContext;

class SwitchContextRequest extends FormRequest
{
    /**
     * Authorize the request.
     *
     * We do NOT check permissions here.
     * Authentication is already enforced by `auth:sanctum`
     * and authorization is handled by middleware/services.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'context_id' => [
                'required',
                'integer',
                'exists:user_contexts,id',
            ],
        ];
    }

    /**
     * Additional validation after base rules pass.
     *
     * Ensures:
     *  - context belongs to authenticated user
     *  - context is not already active
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();

            if (! $user) {
                return;
            }

            $contextId = $this->input('context_id');

            /** @var UserContext|null $context */
            $context = UserContext::where('id', $contextId)
                ->where('user_id', $user->id)
                ->first();

            if (! $context) {
                $validator->errors()->add(
                    'context_id',
                    'The selected context does not belong to the authenticated user.'
                );
                return;
            }

            if ($context->is_active_context) {
                $validator->errors()->add(
                    'context_id',
                    'This context is already active.'
                );
            }
        });
    }

    /**
     * Custom error messages (optional but recommended).
     */
    public function messages(): array
    {
        return [
            'context_id.required' => 'Context ID is required.',
            'context_id.exists'   => 'The selected context does not exist.',
        ];
    }
}
