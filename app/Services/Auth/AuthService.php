<?php

namespace App\Services\Auth;

use App\Http\Resources\Auth\UserResource;
use App\Models\Auth\UserAssignment;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthService
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private UserContextService $contextService
    ) {}

    /* =========================================================
     | INTERNAL HELPERS
     ========================================================= */

    protected function makeResource(
        Authenticatable|Model $user
    ): UserResource {

        $user->loadMissing([
            'userAssignments.role.permissions',
            'userContexts',
        ]);

        return new UserResource($user->refresh());
    }

    /**
     * Context is required ONLY for non-admin users
     */
    protected function ensureContextExists(User $user): void
    {
        if ($user->is_admin) {
            return; // 🔥 HARD ADMIN BYPASS
        }

        if (! $user->activeContext()) {
            throw new AccessDeniedHttpException('No active context selected.');
        }
    }

    protected function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /* =========================================================
     | TOKEN ISSUANCE
     ========================================================= */

    public function issuePreContextToken(User $user): string
    {
        $this->revokeAllTokens($user);

        return $user
            ->createToken('pre-context-token', ['context.select'])
            ->plainTextToken;
    }

    public function issueContextToken(User $user): string
    {
        // 🔥 Admin never needs context
        if ($user->is_admin) {
            $this->revokeAllTokens($user);

            return $user
                ->createToken('system-token', ['*'])
                ->plainTextToken;
        }

        $this->ensureContextExists($user);

        $this->revokeAllTokens($user);

        $abilities = $this->authorizationService
            ->resolveTokenAbilities($user);

        return $user
            ->createToken('context-token', $abilities)
            ->plainTextToken;
    }

    /* =========================================================
     | LOGIN
     ========================================================= */

    public function login(
        string $email,
        string $password
    ): array {

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new ValidationException('Invalid credentials.');
        }

        if (! $user->is_active) {
            throw new AccessDeniedHttpException('Account disabled.');
        }

        /*
        |----------------------------------------------------------------------
        | 🔥 SYSTEM ADMIN (FULL BYPASS)
        |----------------------------------------------------------------------
        */

        if ($user->is_admin) {

            $this->revokeAllTokens($user);

            return [
                'token' => $user
                    ->createToken('system-token', ['*'])
                    ->plainTextToken,
                'user' => $this->makeResource($user),
                'context_required' => false,
            ];
        }

        /*
        |----------------------------------------------------------------------
        | 🔍 BUSINESS USER
        |----------------------------------------------------------------------
        */

        $assignments = UserAssignment::query()
            ->with('assignable') // 🔥 REQUIRED for org resolution
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->get();

        if ($assignments->isEmpty()) {
            throw new AccessDeniedHttpException(
                'No active role assigned. Please contact administrator.'
            );
        }

        /*
        |----------------------------------------------------------------------
        | AUTO CONTEXT IF SINGLE ASSIGNMENT
        |----------------------------------------------------------------------
        */

        if ($assignments->count() === 1) {

            if (! $user->activeContext()) {

                $this->contextService->switchByAssignment(
                    $user,
                    $assignments->first()->id
                );

                $user->refresh();
            }

            return [
                'token' => $this->issueContextToken($user),
                'user' => $this->makeResource($user),
                'context_required' => false,
            ];
        }

        /*
        |----------------------------------------------------------------------
        | 🔥 UPDATED: SMART AUTO CONTEXT RESOLUTION
        |----------------------------------------------------------------------
        | If multiple assignments:
        | 1. Group by organization
        | 2. If only ONE organization → auto-pick highest scope
        | 3. If multiple organizations → require manual selection
        */

        // 🔥 Group assignments by organization
        $groupedByOrg = $assignments->groupBy(function ($assignment) {

            // Organization assignment has no organization_id property
            if ($assignment->assignable_type === 'organization') {
                return $assignment->assignable_id;
            }

            return $assignment->assignable->organization_id;
        });

        // ✅ If user belongs to ONLY ONE organization
        if ($groupedByOrg->count() === 1) {

            // 🔥 Scope priority (highest first)
            $priority = [
                'organization' => 1,
                'branch' => 2,
                'warehouse' => 3,
                'outlet' => 4,
            ];

            // Select highest-level scope
            $selectedAssignment = $assignments
                ->sortBy(fn ($a) => $priority[$a->assignable_type] ?? 999)
                ->first();

            // Switch context automatically
            $this->contextService->switchByAssignment(
                $user,
                $selectedAssignment->id
            );

            $user->refresh();

            return [
                'token' => $this->issueContextToken($user),
                'user' => $this->makeResource($user),
                'context_required' => false,
            ];
        }

        /*
        |----------------------------------------------------------------------
        | MULTIPLE ORGANIZATIONS → MANUAL CONTEXT REQUIRED
        |----------------------------------------------------------------------
        */

        return [
            'token' => $this->issuePreContextToken($user),
            'user' => $this->makeResource($user),
            'context_required' => true,
        ];
    }

    /* =========================================================
     | LOGOUT
     ========================================================= */

    public function logout(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /* =========================================================
     | PASSWORD RESET
     ========================================================= */

    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $data): string
    {
        return Password::reset($data, function (User $user, $password) {

            $user->password = Hash::make($password);
            $user->setRememberToken(Str::random(60));
            $user->save();

            event(new PasswordReset($user));
        });
    }

    /* =========================================================
     | PROFILE
     ========================================================= */

    public function profile(User $user): UserResource
    {
        return $this->makeResource($user);
    }
}