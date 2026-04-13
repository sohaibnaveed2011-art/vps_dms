<?php

namespace App\Guards;

use App\Contracts\ContextScope;
use App\Exceptions\ForbiddenException;
use App\Models\User;
use App\Services\Policy\AuthorityPolicyService;

class AuthorityGuard
{
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

            if ($this->policy->can(
                orgId: $context->organization_id,
                roleId: $roleId,
                subject: $subject,
                action: $action,
                voucherType: $voucherType,
                hierarchyType: $hierarchyType,
                hierarchyId: $hierarchyId
            )) {
                return;
            }
        }

        throw new ForbiddenException('Authority denied');
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
        return match (true) {
            $target->outletId()    => ['outlet', $target->outletId()],
            $target->warehouseId() => ['warehouse', $target->warehouseId()],
            $target->branchId()    => ['branch', $target->branchId()],
            default                => ['organization', $target->organizationId()],
        };
    }
}
