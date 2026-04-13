<?php

namespace App\Guards;

use App\Exceptions\ForbiddenException;
use App\Services\Policy\OrganizationPolicyService;

class PolicyGuard
{
    public function __construct(
        protected OrganizationPolicyService $policy
    ) {}

    /* ================= FEATURE ================= */

    public function requireFeature(int $organizationId, string $feature): void {
        if (! $this->policy->featureEnabled($organizationId, $feature)) {
            throw new ForbiddenException("Feature [{$feature}] is disabled");
        }
    }

    /* ================= HIERARCHY ================= */

    public function requireHierarchy(int $organizationId, string $entity): void {
        if (! $this->policy->hierarchyEnabled($organizationId, $entity)) {
            throw new ForbiddenException(ucfirst($entity).' hierarchy is disabled');
        }
    }

    /* ================= PATH ================= */

    public function requireAllowedPath(int $organizationId, array $path): void {
        if (! $this->policy->isPathAllowed($organizationId, $path)) {
            throw new ForbiddenException('Hierarchy path not allowed');
        }
    }

    public function requireWithinLimit(
    int $organizationId,
    string $entity,
    int $currentCount
    ): void {

        $limit = $this->policy->getLimit($organizationId, $entity);

        if ($limit !== null && $currentCount >= $limit) {
            throw new ForbiddenException(
                ucfirst($entity)." limit ({$limit}) exceeded."
            );
        }
    }
}
