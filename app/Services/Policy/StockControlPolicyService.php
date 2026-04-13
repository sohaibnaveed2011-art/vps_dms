<?php

namespace App\Services\Policy;

use App\Models\Governance\StockControlPolicy;
use Illuminate\Support\Facades\Cache;

class StockControlPolicyService extends BasePolicyService
{
    public function getValue(
        int $orgId,
        string $key,
        $default = null
    ) {
        return Cache::tags($this->tag($orgId))
            ->rememberForever(
                "stock_control:{$orgId}:{$key}",
                fn () =>
                    StockControlPolicy::query()
                        ->where('organization_id', $orgId)
                        ->where('key', $key)
                        ->value('value') ?? $default
            );
    }

    public function create(
        int $orgId,
        string $key,
        array $value,
        ?string $description = null
    ): StockControlPolicy {

        $this->ensureOrganizationNotLocked($orgId);

        $policy = StockControlPolicy::create([
            'organization_id' => $orgId,
            'key' => $key,
            'value' => $value,
            'description' => $description,
        ]);

        $this->flushOrganization($orgId);

        return $policy;
    }

    public function update(
        StockControlPolicy $policy,
        array $value
    ): void {

        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $policy->update(['value' => $value]);

        $this->flushOrganization($policy->organization_id);
    }

    public function delete(StockControlPolicy $policy): void
    {
        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $policy->delete();

        $this->flushOrganization($policy->organization_id);
    }
}