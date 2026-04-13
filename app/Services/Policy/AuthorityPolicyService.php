<?php

namespace App\Services\Policy;

use App\Models\Governance\AuthorityPolicy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class AuthorityPolicyService extends BasePolicyService
{
    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function paginate(
        int $orgId,
        int $perPage = 15
    ): LengthAwarePaginator {

        return AuthorityPolicy::query()
            ->where('organization_id', $orgId)
            ->orderBy('subject')
            ->orderBy('action')
            ->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK AUTHORITY (CACHED + STRICT + DENY PRECEDENCE)
    |--------------------------------------------------------------------------
    */

    public function can(
        int $orgId,
        int $roleId,
        string $subject,
        ?string $action = null,
        ?string $voucherType = null,
        ?string $hierarchyType = null,
        ?int $hierarchyId = null
    ): bool {

        $cacheKey = $this->cacheKey(
            $orgId,
            $roleId,
            $subject,
            $action,
            $voucherType,
            $hierarchyType,
            $hierarchyId
        );

        return Cache::tags($this->tag($orgId))
            ->rememberForever($cacheKey, function () use (
                $orgId,
                $roleId,
                $subject,
                $action,
                $voucherType,
                $hierarchyType,
                $hierarchyId
            ) {

                $policies = AuthorityPolicy::query()
                    ->where('organization_id', $orgId)
                    ->where('role_id', $roleId)
                    ->where('subject', $subject)
                    ->get();

                if ($policies->isEmpty()) {
                    return false;
                }

                $candidates = $policies->filter(function ($p) use (
                    $action,
                    $voucherType,
                    $hierarchyType,
                    $hierarchyId
                ) {

                    // Data integrity protection
                    if (!is_null($p->hierarchy_id) && is_null($p->hierarchy_type)) {
                        return false;
                    }

                    if (!is_null($p->action) && $p->action !== $action) {
                        return false;
                    }

                    if (!is_null($p->voucher_type) &&
                        $p->voucher_type !== $voucherType) {
                        return false;
                    }

                    if (!is_null($p->hierarchy_type) &&
                        $p->hierarchy_type !== $hierarchyType) {
                        return false;
                    }

                    if (!is_null($p->hierarchy_id) &&
                        $p->hierarchy_id !== $hierarchyId) {
                        return false;
                    }

                    return true;
                });

                if ($candidates->isEmpty()) {
                    return false;
                }

                // 🎯 Improved specificity scoring
                $scored = $candidates->map(function ($p) {

                    $score = 0;

                    if (!is_null($p->action))        $score += 8;
                    if (!is_null($p->voucher_type))  $score += 4;
                    if (!is_null($p->hierarchy_id))  $score += 2;
                    if (!is_null($p->hierarchy_type))$score += 1;

                    return [
                        'policy' => $p,
                        'score'  => $score
                    ];
                });

                $maxScore = $scored->max('score');

                $top = $scored
                    ->where('score', $maxScore)
                    ->pluck('policy');

                // Deny overrides allow
                if ($top->contains(fn ($p) => $p->effect === 'deny')) {
                    return false;
                }

                return $top->contains(fn ($p) => $p->effect === 'allow');
            });
    }

    /*
    |--------------------------------------------------------------------------
    | CACHE KEY
    |--------------------------------------------------------------------------
    */

    protected function cacheKey(
        int $orgId,
        int $roleId,
        string $subject,
        ?string $action,
        ?string $voucherType,
        ?string $hierarchyType,
        ?int $hierarchyId
    ): string {

        return implode(':', [
            'authority',
            $orgId,
            $roleId,
            $subject,
            $action ?? 'null',
            $voucherType ?? 'null',
            $hierarchyType ?? 'null',
            $hierarchyId ?? 'null',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATION (LOCK SAFE + CACHE FLUSH)
    |--------------------------------------------------------------------------
    */

    public function create(int $orgId, array $data): AuthorityPolicy
    {
        $this->ensureOrganizationNotLocked($orgId);

        $policy = AuthorityPolicy::create([
            'organization_id' => $orgId,
            ...$data,
            'is_locked' => false,
        ]);

        $this->flushOrganization($orgId);

        return $policy;
    }

    public function update(
        AuthorityPolicy $policy,
        array $data
    ): void {

        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $this->ensureRowNotLocked($policy);

        $policy->update($data);

        $this->flushOrganization($policy->organization_id);
    }

    public function delete(AuthorityPolicy $policy): void
    {
        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $this->ensureRowNotLocked($policy);

        $policy->delete();

        $this->flushOrganization($policy->organization_id);
    }

    public function lock(AuthorityPolicy $policy): void
    {
        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $policy->update(['is_locked' => true]);

        $this->flushOrganization($policy->organization_id);
    }

    public function unlock(AuthorityPolicy $policy): void
    {
        $this->ensureOrganizationNotLocked(
            $policy->organization_id
        );

        $policy->update(['is_locked' => false]);

        $this->flushOrganization($policy->organization_id);
    }
}
