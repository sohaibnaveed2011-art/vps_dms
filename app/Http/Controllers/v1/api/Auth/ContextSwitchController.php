<?php

namespace App\Http\Controllers\v1\api\Auth;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Auth\SwitchContextByAssignmentRequest;
use App\Http\Resources\Auth\UserContextResource;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\UserContextService;
use Illuminate\Http\JsonResponse;

class ContextSwitchController extends BaseApiController
{
    public function __construct(
        protected UserContextService $contextService,
        protected AuthService $authService
    ) {
        parent::__construct();
    }

    /**
     * Switch user context using an active assignment.
     *
     * POST /context/switch
     * {
     *   "assignment_id": 12
     * }
     */
    public function switch(SwitchContextByAssignmentRequest $request): JsonResponse {
        $user = $request->user();

        // 1️⃣ Create context from assignment
        $context = $this->contextService->switchByAssignment(
            $user,
            $request->validated()['assignment_id']
        );

        // 2️⃣ Issue CONTEXT-AWARE token
        $token = $this->authService->issueContextToken($user);

        return $this->success([
            'token'   => $token,
            'context' => new UserContextResource($context),
            'user'    => new UserResource($user->refresh()),
        ]);
    }
}
