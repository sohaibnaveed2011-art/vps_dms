<?php

namespace App\Services\Policy;

use App\Exceptions\ForbiddenException;
use App\Models\Core\Organization;
use Illuminate\Support\Facades\Cache;

abstract class BasePolicyService
{
    /*
    |--------------------------------------------------------------------------
    | SHARED CACHE NAMESPACE
    |--------------------------------------------------------------------------
    */

    protected function tag(int $organizationId): array
    {
        return ["org:{$organizationId}:policies"];
    }

    protected function flushOrganization(int $organizationId): void
    {
        Cache::tags($this->tag($organizationId))->flush();
    }

    /*
    |--------------------------------------------------------------------------
    | GLOBAL LOCK ENFORCEMENT
    |--------------------------------------------------------------------------
    */

    protected function ensureOrganizationNotLocked(int $organizationId): void
    {
        $locked = Organization::where('id', $organizationId)
            ->value('policies_locked');

        if ($locked) {
            throw new ForbiddenException(
                'Organization policies are globally locked.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ROW LOCK ENFORCEMENT
    |--------------------------------------------------------------------------
    */

    protected function ensureRowNotLocked(object $model): void
    {
        if (isset($model->is_locked) && $model->is_locked) {
            throw new ForbiddenException(
                'This policy row is locked.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GLOBALLY LOCK ENFORCEMENT
    |--------------------------------------------------------------------------
    */

    public function lockOrganizationPolicies(int $organizationId): void
    {
        Organization::where('id', $organizationId)
            ->update(['policies_locked' => true]);

        $this->flushOrganization($organizationId);
    }

    public function unlockOrganizationPolicies(int $organizationId): void
    {
        Organization::where('id', $organizationId)
            ->update(['policies_locked' => false]);

        $this->flushOrganization($organizationId);
    }

}
