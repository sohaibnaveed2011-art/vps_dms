<?php

namespace App\Guards;

use App\Exceptions\ForbiddenException;
use App\Models\Auth\UserContext;
use App\Services\Policy\OrganizationPolicyService;

class HierarchyScopeGuard
{
    public function __construct(
        protected OrganizationPolicyService $policy
    ) {}

    public function enforce(UserContext $context): void
    {
        $orgId = $context->organization_id;

        $this->validateHierarchy(
            $orgId,
            'branch',
            $context->branch
        );

        $this->validateHierarchy(
            $orgId,
            'warehouse',
            $context->warehouse
        );

        $this->validateHierarchy(
            $orgId,
            'outlet',
            $context->outlet
        );
    }

    protected function validateHierarchy(
        int $orgId,
        string $type,
        $entity
    ): void {

        $enabled = $this->policy->hierarchyEnabled($orgId, $type);

        // ❌ If disabled but entity exists → block
        if (! $enabled && $entity) {
            throw new ForbiddenException(
                ucfirst($type) . ' hierarchy is disabled.'
            );
        }

        // ❌ If enabled but entity belongs to another org → block
        if (
            $enabled &&
            $entity &&
            $entity->organization_id !== $orgId
        ) {
            throw new ForbiddenException(
                "Invalid {$type} context."
            );
        }
    }
}