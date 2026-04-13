<?php

namespace App\Services\Policy;

use App\Exceptions\NotFoundException;
use App\Models\Governance\OrganizationPolicy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class OrganizationPolicyService extends BasePolicyService
{
    /* =========================================================
     | READ
     ========================================================= */

    public function get(int $orgId, string $key): array
    {
        return Cache::tags($this->tag($orgId))
            ->rememberForever(
                "org_policy:{$orgId}:{$key}",
                function () use ($orgId, $key) {

                    $policy = OrganizationPolicy::query()
                        ->where('organization_id', $orgId)
                        ->where('key', $key)
                        ->first();

                    if (! $policy) {
                        throw new NotFoundException("Policy [{$key}] not found.");
                    }

                    return $policy->value ?? [];
                }
            );
    }

    public function getValue(
        int $orgId,
        string $key,
        string $path,
        $default = null
    ) {
        return data_get(
            $this->get($orgId, $key),
            $path,
            $default
        );
    }

    public function getLimit(int $orgId, string $entity): ?int
    {
        return $this->getValue(
            $orgId,
            'organization.structure',
            "limits.{$entity}",
            null
        );
    }

    public function list(
        int $orgId,
        ?string $category = null,
        int $perPage = 15
    ): LengthAwarePaginator {

        return OrganizationPolicy::query()
            ->where('organization_id', $orgId)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('category')
            ->orderBy('key')
            ->paginate($perPage);
    }

    public function exists(int $orgId, string $key): bool
    {
        return OrganizationPolicy::query()
            ->where('organization_id', $orgId)
            ->where('key', $key)
            ->exists();
    }

    /* =========================================================
     | FEATURE / HIERARCHY HELPERS
     ========================================================= */

    public function featureEnabled(int $orgId, string $feature): bool
    {
        return (bool) $this->getValue(
            $orgId,
            'organization.features',
            $feature,
            false
        );
    }

    public function hierarchyEnabled(int $orgId, string $entity): bool
    {
        return (bool) $this->getValue(
            $orgId,
            'organization.structure',
            "hierarchy.{$entity}",
            false
        );
    }

    public function isPathAllowed(int $orgId, array $path): bool
    {
        $allowed = $this->getValue(
            $orgId,
            'organization.structure',
            'allowed_paths',
            []
        );

        return collect($allowed)
            ->contains(fn ($p) => $p === $path);
    }

    /* =========================================================
     | MUTATION
     ========================================================= */

    public function create(
        int $orgId,
        string $key,
        array $value,
        ?string $category = null,
        ?string $description = null
    ): OrganizationPolicy {

        $this->ensureOrganizationNotLocked($orgId);

        $policy = OrganizationPolicy::create([
            'organization_id' => $orgId,
            'key' => $key,
            'category' => $category,
            'value' => $value,
            'description' => $description,
            'is_locked' => false,
        ]);

        $this->flushOrganization($orgId);

        return $policy;
    }

    public function update(
        int $orgId,
        string $key,
        array $value
    ): void {

        $this->ensureOrganizationNotLocked($orgId);

        $policy = OrganizationPolicy::query()
            ->where('organization_id', $orgId)
            ->where('key', $key)
            ->firstOrFail();

        $this->ensureRowNotLocked($policy);

        $policy->update(['value' => $value]);

        $this->flushOrganization($orgId);
    }

    public function delete(
        OrganizationPolicy $policy
    ): void {

        $this->ensureOrganizationNotLocked($policy->organization_id);
        $this->ensureRowNotLocked($policy);

        $policy->delete();

        $this->flushOrganization($policy->organization_id);
    }

    public function lock(OrganizationPolicy $policy): void
    {
        $this->ensureOrganizationNotLocked($policy->organization_id);

        $policy->update(['is_locked' => true]);

        $this->flushOrganization($policy->organization_id);
    }

    public function unlock(OrganizationPolicy $policy): void
    {
        $this->ensureOrganizationNotLocked($policy->organization_id);

        $policy->update(['is_locked' => false]);

        $this->flushOrganization($policy->organization_id);
    }
}
