<?php

namespace App\Http\Controllers\v1\api\Auth;

use App\Exceptions\ConflictException;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AuthController extends BaseApiController
{
    // No permissions required for Auth routes (Login/Forgot Pass)
    protected array $permissions = [];

    public function __construct(protected AuthService $service)
    {
        parent::__construct();
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        return $this->success(
            $this->service->login($credentials['email'], $credentials['password'])
        );
    }

    /**
     * Logout current user.
     */
    public function logout(): JsonResponse
    {
        $this->service->logout();
        return $this->success(['message' => 'Logged out successfully']);
    }

    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        // $request->user() is handled by the auth middleware;
        // if it reaches here, the user exists.
        return $this->success(
            $this->service->profile($request->user())
        );
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->service->sendResetLink($request->validated()['email']);

        return $status === Password::RESET_LINK_SENT
            ? $this->success(['message' => __($status)])
            : throw new ConflictException(400);
    }

    /**
     * Reset password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->service->resetPassword($request->validated());

        return $status === Password::PASSWORD_RESET
            ? $this->success(['message' => __($status)])
            : throw new ConflictException(400);
    }
}
