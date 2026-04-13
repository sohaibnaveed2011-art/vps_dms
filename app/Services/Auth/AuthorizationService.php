<?php

namespace App\Services\Auth;

use App\Contracts\ContextScope;
use App\Exceptions\ForbiddenException;
use App\Guards\AuthorityGuard;
use App\Guards\LocationOperationGuard;
use App\Models\Auth\UserAssignment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AuthorizationService
{
    public function __construct(
        protected AuthorityGuard $authorityGuard
    ) {}

    /* =========================================================
     | AUTHORIZE WRAPPER
     ========================================================= */

    public function authorize(
        User $user,
        string $permission,
        ?Model $target = null,
    ): void {

        if (! $this->can($user, $permission, $target)) {

            AuditLogger::log(
                $user->id,
                'authorization_denied',
                $target,
                ['permission' => $permission]
            );

            throw new ForbiddenException('Unauthorized.');
        }
    }

    /* =========================================================
     | CORE CHECK
     ========================================================= */

    public function can(
        User $user,
        string $permission,
        ?Model $target = null
    ): bool {
        
        if ($user->is_admin) {
            return true;
        }

        $context = $user->activeContext();

        if (! $context) {
            return false;
        }

        $assignments = $this->activeAssignments($user);

        $validRoles = [];

        foreach ($assignments as $assignment) {

            if (! $assignment->role->hasPermissionTo($permission)) {
                continue;
            }

            if (! $this->matchesScope($assignment, $target)) {
                continue;
            }

            $validRoles[] = $assignment->role_id;
        }

        if (empty($validRoles)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | 🔐 AUTOMATIC AUTHORITY OVERLAY (VOUCHER REVIEW / APPROVE ONLY)
        |--------------------------------------------------------------------------
        */

        $params = $this->resolveAuthorityParams($permission);

        $requiresAuthority =
            $params['domain'] === 'vouchers' &&
            in_array($params['action'], ['review', 'approve']);

        if ($requiresAuthority) {

            $subject = "{$params['domain']}.{$params['subject']}";
            $action  = $params['action'];
            $voucherType = $params['subject']; // auto-derived

            try {
                $this->authorityGuard->enforce(
                    $user,
                    $subject,
                    $action,
                    $target,
                    $voucherType,
                    $validRoles
                );
            } catch (ForbiddenException) {
                return false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 📍 CONTEXT LOCATION BOUNDARY ENFORCEMENT
        |--------------------------------------------------------------------------
        */

        if ($target instanceof ContextScope) {
            if (! LocationOperationGuard::canOperateOn($user, $target)) {
                return false;
            }
        }

        return true;
    }

    /* =========================================================
     | ACTIVE ASSIGNMENTS (CACHED)
     ========================================================= */

    protected function activeAssignments(User $user): Collection
    {
        $context = $user->activeContext();

        if (! $context) {
            return collect();
        }

        $cacheKey = implode(':', [
            'user',
            $user->id,
            'assignments',
            $context->organization_id,
            $context->branch_id ?? 'null',
            $context->warehouse_id ?? 'null',
            $context->outlet_id ?? 'null',
        ]);

        return Cache::tags([
            "user:{$user->id}",
            "org:{$context->organization_id}"
        ])->remember($cacheKey, now()->addMinutes(10), function () use ($user, $context) {

            $hierarchyIds = array_filter([
                'organization' => $context->organization_id,
                'branch'       => $context->branch_id,
                'warehouse'    => $context->warehouse_id,
                'outlet'       => $context->outlet_id,
            ]);

            return UserAssignment::query()
                ->where('user_id', $user->id)
                ->whereNull('ended_at')
                ->where(function ($q) use ($hierarchyIds) {
                    foreach ($hierarchyIds as $type => $id) {
                        $q->orWhere(function ($sub) use ($type, $id) {
                            $sub->where('assignable_type', $type)
                                ->where('assignable_id', $id);
                        });
                    }
                })
                ->with('role.permissions')
                ->get();
        });
    }

    /* =========================================================
     | ASSIGNMENT SCOPE MATCHING
     ========================================================= */

    protected function matchesScope(UserAssignment $assignment, ?Model $target): bool
    {
        if (! $target instanceof ContextScope) {
            return true;
        }

        $hierarchy = [
            'outlet'       => $target->outletId(),
            'warehouse'    => $target->warehouseId(),
            'branch'       => $target->branchId(),
            'organization' => $target->organizationId(),
        ];

        foreach ($hierarchy as $type => $id) {
            if ($id &&
                $assignment->assignable_type === $type &&
                $assignment->assignable_id === $id
            ) {
                return true;
            }
        }

        return false;
    }

    /* =========================================================
     | PERMISSION PARSER
     ========================================================= */

    protected function resolveAuthorityParams(string $permission): array
    {
        $parts = explode('.', $permission);

        return [
            'domain'  => $parts[0] ?? null,
            'subject' => $parts[1] ?? null,
            'action'  => $parts[2] ?? null,
        ];
    }

    /* =========================================================
     | TOKEN ABILITY RESOLUTION
     ========================================================= */

    public function resolveTokenAbilities(User $user): array
    {
        // 🔥 Admin always gets full abilities
        if ($user->is_admin) {
            return ['*'];
        }

        $context = $user->activeContext();

        if (! $context) {
            return [];
        }

        $assignments = $this->activeAssignments($user);

        $domains = collect();

        foreach ($assignments as $assignment) {
            foreach ($assignment->role->permissions as $permission) {

                $parts = explode('.', $permission->name);

                if (! empty($parts[0])) {
                    $domains->push($parts[0]);
                }
            }
        }

        return $domains
            ->unique()
            ->map(fn ($domain) => "{$domain}.*")
            ->values()
            ->toArray();
    }
}
