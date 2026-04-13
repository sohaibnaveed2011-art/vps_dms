<?php

namespace App\Services\Policy;

use App\Models\Governance\StockFlowPolicy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class StockFlowPolicyService extends BasePolicyService
{
    /*
    |--------------------------------------------------------------------------
    | LIST
    |--------------------------------------------------------------------------
    */

    public function paginate(
        int $orgId,
        ?string $fromType = null,
        ?string $toType = null,
        int $perPage = 15
    ): LengthAwarePaginator {

        return StockFlowPolicy::query()
            ->where('organization_id', $orgId)
            ->when($fromType,
                fn ($q) => $q->where('from_type', $fromType)
            )
            ->when($toType,
                fn ($q) => $q->where('to_type', $toType)
            )
            ->orderBy('from_type')
            ->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK FLOW
    |--------------------------------------------------------------------------
    */

    public function isAllowed(
        int $orgId,
        string $fromType,
        ?int $fromId,
        string $toType,
        ?int $toId
    ): bool {

        return Cache::tags($this->tag($orgId))
            ->rememberForever(
                "flow:{$orgId}:{$fromType}:{$fromId}:{$toType}:{$toId}",
                function () use ($orgId, $fromType, $fromId, $toType, $toId) {

                    return StockFlowPolicy::query()
                        ->where('organization_id', $orgId)
                        ->where('from_type', $fromType)
                        ->where('to_type', $toType)
                        ->where(function ($q) use ($fromId) {
                            $q->whereNull('from_id')
                              ->orWhere('from_id', $fromId);
                        })
                        ->where(function ($q) use ($toId) {
                            $q->whereNull('to_id')
                              ->orWhere('to_id', $toId);
                        })
                        ->where('allowed', true)
                        ->exists();
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATION
    |--------------------------------------------------------------------------
    */

    public function create(int $orgId, array $data): StockFlowPolicy
    {
        $this->ensureOrganizationNotLocked($orgId);

        $policy = StockFlowPolicy::create([
            'organization_id' => $orgId,
            ...$data,
            'is_locked' => false,
        ]);

        $this->flushOrganization($orgId);

        return $policy;
    }

    public function update(
        StockFlowPolicy $policy,
        array $data
    ): void {

        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $this->ensureRowNotLocked($policy);

        $policy->update($data);

        $this->flushOrganization($policy->organization_id);
    }

    public function delete(StockFlowPolicy $policy): void
    {
        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $this->ensureRowNotLocked($policy);

        $policy->delete();

        $this->flushOrganization($policy->organization_id);
    }

    public function lock(StockFlowPolicy $policy): void
    {
        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $policy->update(['is_locked' => true]);

        $this->flushOrganization($policy->organization_id);
    }

    public function unlock(StockFlowPolicy $policy): void
    {
        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $policy->update(['is_locked' => false]);

        $this->flushOrganization($policy->organization_id);
    }
}
