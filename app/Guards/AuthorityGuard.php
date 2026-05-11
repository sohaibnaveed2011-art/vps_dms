<?php

namespace App\Guards;

use App\Contracts\ContextScope;
use App\Exceptions\ForbiddenException;
use App\Models\User;
use App\Services\Policy\AuthorityPolicyService;

class AuthorityGuard
{
    protected array $policyCache = [];
    public function __construct(
        protected AuthorityPolicyService $policy
    ) {}

    /*
    |--------------------------------------------------------------------------
    | AUTHORITY ENFORCEMENT
    |--------------------------------------------------------------------------
    |
    | Applies ONLY for voucher review/approve actions.
    | Target MUST implement ContextScope.
    |
    */

    public function enforce(
        User $user,
        string $subject,
        ?string $action,
        ?ContextScope $target,
        ?string $voucherType,
        array $roleIds
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Active Context Required
        |--------------------------------------------------------------------------
        */

        $context = $user->activeContext()
            ?? throw new ForbiddenException('No active context');

        /*
        |--------------------------------------------------------------------------
        | Target Must Be Context-Aware (Voucher Model)
        |--------------------------------------------------------------------------
        */

        if (! $target instanceof ContextScope) {
            throw new ForbiddenException('Invalid authority target.');
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve Target Hierarchy
        |--------------------------------------------------------------------------
        */

        [$hierarchyType, $hierarchyId] =
            $this->resolveHierarchy($target);

        /*
        |--------------------------------------------------------------------------
        | Role-Based Authority Evaluation
        |--------------------------------------------------------------------------
        |
        | If ANY valid role allows → success
        | Otherwise → denied
        |
        */

        foreach ($roleIds as $roleId) {
            $cacheKey = $this->getCacheKey(
                $context->organization_id,
                $roleId,
                $subject,
                $action,
                $voucherType,
                $hierarchyType,
                $hierarchyId
            );
            
            if (isset($this->policyCache[$cacheKey])) {
                if ($this->policyCache[$cacheKey]) {
                    return;
                }
                continue;
            }
            
            $allowed = $this->policy->can(
                orgId: $context->organization_id,
                roleId: $roleId,
                subject: $subject,
                action: $action,
                voucherType: $voucherType,
                hierarchyType: $hierarchyType,
                hierarchyId: $hierarchyId
            );
            
            $this->policyCache[$cacheKey] = $allowed;

            if ($allowed) {
                return;
            }
        }

        throw new ForbiddenException('Authority denied');
    }

    protected function getCacheKey(...$params): string
    {
        return md5(implode('|', $params));
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Hierarchy From Target
    |--------------------------------------------------------------------------
    |
    | Most specific → least specific
    |
    */

    protected function resolveHierarchy(ContextScope $target): array
    {
        // Add support for custom hierarchy resolution
        if (method_exists($target, 'getAuthorityHierarchy')) {
            return $target->getAuthorityHierarchy();
        }
        return match (true) {
            $target->outletId()    => ['outlet', $target->outletId()],
            $target->warehouseId() => ['warehouse', $target->warehouseId()],
            $target->branchId()    => ['branch', $target->branchId()],
            default                => ['organization', $target->organizationId()],
        };
    }
}
